<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Company\Config;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\PurchaseOrder\Model\Company\ConfigInterface;

/**
 * Purchase Order Config Repository interface
 *
 * @api
 */
interface RepositoryInterface
{
    /**
     * Get purchase order config by company id
     *
     * @param int $companyId
     * @return ConfigInterface
     */
    public function get($companyId);

    /**
     * Set purchase order config for company
     *
     * @param ConfigInterface $config - company purchase order config
     * @return ConfigInterface
     * @throws CouldNotSaveException
     */
    public function save(ConfigInterface $config);
}
