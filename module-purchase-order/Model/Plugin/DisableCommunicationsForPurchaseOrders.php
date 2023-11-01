<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\PurchaseOrder\Model\Notification\Config;
use Magento\Store\Model\ScopeInterface;

class DisableCommunicationsForPurchaseOrders
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Disable sending email based on the global system configuration setting
     *
     * @param Config $subject
     * @param bool $result
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterIsEnabledForStoreView(Config $subject, bool $result): bool
    {
        if ($this->scopeConfig->isSetFlag('system/smtp/disable', ScopeInterface::SCOPE_STORE)) {
            $result = false;
        }
        return $result;
    }
}
