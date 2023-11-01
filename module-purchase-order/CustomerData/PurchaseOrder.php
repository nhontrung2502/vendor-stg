<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\PurchaseOrder\Model\Config;

/**
 * Customer Data Section Provider for Purchase Order Data
 */
class PurchaseOrder implements SectionSourceInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Add order approval section data depending on its enabled status
     *
     * @return array
     */
    public function getSectionData()
    {
        return [
            'isEnabled' => $this->config->isEnabledForCurrentCustomerAndWebsite()
        ];
    }
}
