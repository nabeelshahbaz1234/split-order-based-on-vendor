<?php
declare(strict_types=1);

namespace RltSquare\SplitOrder\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    /**
     * Check if module is active.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isActive(int $storeId = null): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(
            'sales/module/enabled',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get attributes to split.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getAttributes(int $storeId = null): string
    {
        return $this->scopeConfig->getValue(
            'sales/module/attributes',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check should split delivery.
     *
     * @param string|null $storeId
     * @return bool
     */
    public function getShippingSplit(string $storeId = null): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(
            'sales/module/shipping',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
