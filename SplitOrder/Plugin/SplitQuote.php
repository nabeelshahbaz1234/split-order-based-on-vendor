<?php
declare(strict_types=1);

namespace RltSquare\SplitOrder\Plugin;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Api\Data\OrderInterface;
use RltSquare\SplitOrder\Api\QuoteHandlerInterface;
use RltSquare\OrderSubmitVidaXlBigBuy\Action\GetOrderExportItems;
use RltSquare\OrderSubmitVidaXlBigBuy\Logger\Logger;

/**
 * Class SplitQuote
 * Interceptor to \Magento\Quote\Model\QuoteManagement
 */
class SplitQuote
{

    private CartRepositoryInterface $quoteRepository;
    private QuoteFactory $quoteFactory;
    private ManagerInterface $eventManager;
    private QuoteHandlerInterface $quoteHandler;
    private GetOrderExportItems $getOrderExportItems;
    private Logger $logger;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteFactory $quoteFactory
     * @param ManagerInterface $eventManager
     * @param QuoteHandlerInterface $quoteHandler
     * @param GetOrderExportItems $getOrderExportItems
     * @param Logger $logger
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        QuoteFactory            $quoteFactory,
        ManagerInterface        $eventManager,
        QuoteHandlerInterface   $quoteHandler,
        GetOrderExportItems     $getOrderExportItems,
        Logger                  $logger
    )
    {
        $this->quoteRepository = $quoteRepository;
        $this->quoteFactory = $quoteFactory;
        $this->eventManager = $eventManager;
        $this->quoteHandler = $quoteHandler;
        $this->getOrderExportItems = $getOrderExportItems;
        $this->logger = $logger;
    }

    /**
     * Places an order for a specified cart.
     *
     * @param QuoteManagement $subject
     * @param callable $proceed
     * @param int $cartId
     * @param string $payment
     * @return mixed
     * @throws LocalizedException
     * @throws GuzzleException
     * @throws Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @see \Magento\Quote\Api\CartManagementInterface
     */
    public function aroundPlaceOrder(QuoteManagement $subject, callable $proceed, int $cartId, $payment = null)
    {
        $currentQuote = $this->quoteRepository->getActive($cartId);

        // Separate all items in quote into new quotes.
        $quotes = $this->quoteHandler->normalizeQuotes($currentQuote);
        if (empty($quotes)) {
            return $result = array_values([($proceed($cartId, $payment))]);
        }
        // Collect list of data addresses.
        $addresses = $this->quoteHandler->collectAddressesData($currentQuote);

        /** @var OrderInterface[] $orders */
        $orders = [];
        $orderIds = [];
        foreach ($quotes as $items) {
            /** @var Quote $split */
            $split = $this->quoteFactory->create();

            // Set all customer definition data.
            $this->quoteHandler->setCustomerData($currentQuote, $split);
            $this->toSaveQuote($split);

            // Map quote items.
            foreach ($items as $item) {
                // Add item by item.
                $item->setId(null);
                $split->addItem($item);
            }
            $this->quoteHandler->populateQuote($quotes, $split, $items, $addresses, $payment);

            // Dispatch event as Magento standard once per each quote split.
            $this->eventManager->dispatch(
                'checkout_submit_before',
                ['quote' => $split]
            );

            $this->toSaveQuote($split);
            $order = $subject->submit($split);

            $orders[] = $order;
            $orderIds[$order->getId()] = $order->getIncrementId();
        }
        $currentQuote->setIsActive(false);
        $this->toSaveQuote($currentQuote);

        $this->quoteHandler->defineSessions($split, $order, $orderIds);

        $this->eventManager->dispatch(
            'checkout_submit_all_after',
            ['orders' => $orders, 'quote' => $currentQuote]
        );

        foreach ($orders as $order) {
            $result = $this->getOrderExportItems->execute($order);

            if (isset($result['vidaXL_order_id'])) {
                $vidaXL_order_id = $result['vidaXL_order_id']['order_id']; // Extracting the ID from the array
                $order->setData('vidaXL_order_id', $vidaXL_order_id);
                $order->save();

                if ($vidaXL_order_id) {
                    $output = __('Successfully exported vidaXL order ') . $vidaXL_order_id;
                    $this->logger->notice($output); // Log the success message
                } else {
                    $msg = $result['error'] ?? null;
                    if ($msg === null) {
                        $msg = __('Unexpected errors occurred');
                    }
                    $this->logger->warning($msg);
                }
            } else {
                // Handle the case where the 'vidaXL_order_id' key is not present in the result.
                $this->logger->error('No vidaXL order ID found in the result.');
            }
        }

        return $this->getOrderKeys($orderIds);
    }

    /**
     * Save quote
     *
     * @param CartInterface $quote
     * @return void
     */
    private function toSaveQuote(CartInterface $quote): void
    {
        $this->quoteRepository->save($quote);

    }

    /**
     * @param array $orderIds
     * @return array
     */
    private function getOrderKeys(array $orderIds): array
    {
        $orderValues = [];
        foreach (array_keys($orderIds) as $orderKey) {
            $orderValues[] = (string)$orderKey;
        }
        return array_values($orderValues);
    }
}
