<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Validator\ActionReady;

use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;

/**
 * Default action ready validator
 */
class DefaultValidator implements ValidatorInterface
{
    /**
     * @inheritDoc
     */
    public function validate(PurchaseOrderInterface $purchaseOrder): bool
    {
        return false;
    }
}
