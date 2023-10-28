<?php

namespace RltSquare\SplitOrder\Api;

use Magento\Catalog\Model\Product;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Interface QuoteHandlerInterface
 * @api
 */
interface QuoteHandlerInterface
{
    /**
     * Separate all items in quote into new quotes.
     *
     * @param Quote $quote
     * @return bool|array False if not split, or split items
     */
    public function normalizeQuotes($quote);

    /**
     * @param Product $product
     * @param string $attributeCode
     * @return string
     */
    public function getProductAttributes($product, $attributeCode);

    /**
     * Collect list of data addresses.
     *
     * @param Quote $quote
     * @return array
     */
    public function collectAddressesData($quote): array;

    /**
     * @param Quote $quote
     * @param Quote $split
     * @return QuoteHandlerInterface
     */
    public function setCustomerData($quote, $split);

    /**
     * Populate quotes with new data.
     *
     * @param array $quotes
     * @param Quote $split
     * @param Item[] $items
     * @param array $addresses
     * @param string $payment
     * @return QuoteHandlerInterface
     */
    public function populateQuote($quotes, $split, $items, $addresses, $payment);

    /**
     * Recollect order totals.
     *
     * @param array $quotes
     * @param Item[] $items
     * @param Quote $quote
     * @param array $addresses
     * @return QuoteHandlerInterface
     */
    public function recollectTotal($quotes, $items, $quote, $addresses);

    /**
     * @param array $quotes
     * @param Quote $quote
     * @param float $total
     */
    public function shippingAmount($quotes, $quote, $total = 0.0);

    /**
     * Set payment method.
     *
     * @param string $paymentMethod
     * @param Quote $split
     * @param string $payment
     * @return QuoteHandlerInterface
     */
    public function setPaymentMethod($paymentMethod, $split, $payment);

    /**
     * Define checkout sessions.
     *
     * @param Quote $split
     * @param AbstractExtensibleModel|OrderInterface|object|null $order
     * @param array $orderIds
     * @return QuoteHandlerInterface
     */
    public function defineSessions($split, $order, $orderIds);
}
