<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Access\Validator;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Model\Customer\Authorization\PlaceOrder as PlaceOrderValidator;
use Magento\PurchaseOrder\Model\Config as PurchaseOrderConfig;

/**
 * Validates access to purchase order for current user.
 */
class PlaceOrder
{
    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * @var PlaceOrderValidator
     */
    private $placeOrderValidator;

    /**
     * @var PurchaseOrderConfig
     */
    private $purchaseOrderConfig;

    /**
     * AccessValidator constructor.
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param PlaceOrderValidator $placeOrderValidator
     * @param PurchaseOrderConfig $purchaseOrderConfig
     */
    public function __construct(
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        PlaceOrderValidator $placeOrderValidator,
        PurchaseOrderConfig $purchaseOrderConfig
    ) {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->placeOrderValidator = $placeOrderValidator;
        $this->purchaseOrderConfig = $purchaseOrderConfig;
    }

    /**
     * Check is place order action allowed.
     *
     * @param int $purchaseOrderId
     * @return bool
     */
    public function validatePlaceOrder($purchaseOrderId) : bool
    {
        if (!$this->purchaseOrderConfig->isEnabledForCurrentCustomerAndWebsite()) {
            return false;
        }
        try {
            $purchaseOrder = $this->purchaseOrderRepository->getById($purchaseOrderId);
        } catch (NoSuchEntityException $exception) {
            return false;
        }
        return $this->placeOrderValidator->isAllowed($purchaseOrder);
    }
}
