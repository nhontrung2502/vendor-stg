<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Notification;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Purchase order notifications config.
 */
class Config
{
    /**
     * Path to config value.
     */
    private const XML_PATH_NOTIFICATION_ENABLED = 'sales_email/purchase_order_notification/enabled';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Config constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Is notification enabled for store view.
     *
     * @param int $storeViewId
     * @return bool
     */
    public function isEnabledForStoreView(int $storeViewId)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_NOTIFICATION_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeViewId
        );
    }
}
