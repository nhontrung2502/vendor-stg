<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Plugin\Company\Model;

use Closure;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\Company\DataProvider as CompanyDataProvider;
use Magento\PurchaseOrder\Model\Company\ConfigInterface;

/**
 * Company Data Provider Plugin for injecting Purchase Order Configuration
 */
class DataProvider
{
    /**
     * Inject purchase order configuration into company settings
     *
     * @param CompanyDataProvider $subject
     * @param Closure $proceed
     * @param CompanyInterface $company
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetSettingsData(
        CompanyDataProvider $subject,
        Closure $proceed,
        CompanyInterface $company
    ) {
        $extensionAttributes = $company->getExtensionAttributes();
        $originalSettings = $proceed($company);

        $settings = [
            'extension_attributes' => [
                ConfigInterface::IS_PURCHASE_ORDER_ENABLED => $extensionAttributes->getIsPurchaseOrderEnabled()
            ]
        ];

        return array_replace_recursive($originalSettings, $settings);
    }
}
