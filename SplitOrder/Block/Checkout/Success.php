<?php
declare(strict_types=1);

namespace RltSquare\SplitOrder\Block\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order\Config;

/**
 * Class Success
 * Overriding Magento success
 */
class Success extends \Magento\Checkout\Block\Onepage\Success
{
    private Session $checkoutSession;

    /**
     * @param Context $context
     * @param Session $checkoutSession
     * @param Config $orderConfig
     * @param HttpContext $httpContext
     * @param array $data
     */
    public function __construct(
        Context     $context,
        Session     $checkoutSession,
        Config      $orderConfig,
        HttpContext $httpContext,
        array       $data = []
    )
    {
        parent::__construct(
            $context,
            $checkoutSession,
            $orderConfig,
            $httpContext,
            $data
        );
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @return bool|array
     */
    public function getOrderArray(): bool|array
    {
        $splitOrders = $this->checkoutSession->getOrderIds();
        $this->checkoutSession->unsOrderIds();

        if (empty($splitOrders) || count($splitOrders) <= 1) {
            return false;
        }
        return $splitOrders;
    }
}
