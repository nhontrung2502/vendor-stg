<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Validator;

use Magento\Framework\Exception\LocalizedException;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Model\Validator\Exception\PurchaseOrderValidationException;

/**
 * Validator interface for purchase order.
 *
 * @api
 */
interface ValidatorInterface
{
    /**
     * Validate purchase order.
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @throws PurchaseOrderValidationException
     * @throws LocalizedException
     */
    public function validate(PurchaseOrderInterface $purchaseOrder) : void;
}
