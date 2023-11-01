<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Notification\Config;

/**
 * Notification config provider interface.
 *
 * @api
 */
interface ProviderInterface
{
    /**
     * Check is notification enabled for entity.
     *
     * @param int $entityId
     * @return bool
     */
    public function isEnabledForEntity(int $entityId) : bool;
}
