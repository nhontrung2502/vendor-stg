<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Customer\Authorization;

use Magento\Company\Model\CompanyAdminPermission;
use Magento\Company\Model\CompanyContext;
use Magento\Company\Model\CompanyManagement;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Model\Customer\Authorization\ActionInterface;
use Magento\PurchaseOrder\Model\Payment\DeferredPaymentStrategy;

/**
 * Place order authorization class.
 */
class PlaceOrder implements ActionInterface
{
    /**
     * @var CompanyAdminPermission
     */
    private $companyAdminPermission;

    /**
     * @var CompanyManagement
     */
    private $companyManagement;

    /**
     * @var CompanyContext
     */
    private $companyContext;

    /**
     * @var DeferredPaymentStrategy
     */
    private $deferredPaymentStrategy;

    /**
     * @param CompanyAdminPermission $companyAdminPermission
     * @param CompanyManagement $companyManagement
     * @param CompanyContext $companyContext
     * @param DeferredPaymentStrategy|null $deferredPaymentStrategy
     */
    public function __construct(
        CompanyAdminPermission $companyAdminPermission,
        CompanyManagement $companyManagement,
        CompanyContext $companyContext,
        DeferredPaymentStrategy $deferredPaymentStrategy = null
    ) {
        $this->companyAdminPermission = $companyAdminPermission;
        $this->companyManagement = $companyManagement;
        $this->companyContext = $companyContext;
        $this->deferredPaymentStrategy = $deferredPaymentStrategy
            ?? ObjectManager::getInstance()->get(DeferredPaymentStrategy::class);
    }

    /**
     * @inheritDoc
     */
    public function isAllowed(PurchaseOrderInterface $purchaseOrder) : bool
    {
        try {
            if ($this->companyContext->isCurrentUserCompanyUser()) {
                $purchaseOrderCompanyId = $purchaseOrder->getCompanyId();
                $currentCustomerCompanyId = $this->companyManagement->getByCustomerId(
                    $this->companyContext->getCustomerId()
                )->getId();
                return $purchaseOrderCompanyId === $currentCustomerCompanyId
                    && $this->canPlaceOrder($purchaseOrder);
            } else {
                return false;
            }
        } catch (NoSuchEntityException $exception) {
            return false;
        } catch (LocalizedException $exception) {
            return false;
        }
    }

    /**
     * Check if the current customer can place order
     *
     * @param PurchaseOrderInterface $purchaseOrder
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    private function canPlaceOrder(PurchaseOrderInterface $purchaseOrder): bool
    {
        if (!$this->deferredPaymentStrategy->isDeferredPayment($purchaseOrder)) {
            return $this->companyAdminPermission->isCurrentUserCompanyAdmin();
        }
        $allowedStatuses = [
            PurchaseOrderInterface::STATUS_APPROVED_PENDING_PAYMENT,
            PurchaseOrderInterface::STATUS_ORDER_FAILED
        ];
        return in_array($purchaseOrder->getStatus(), $allowedStatuses) &&
            (int)$purchaseOrder->getCreatorId() === (int)$this->companyContext->getCustomerId();
    }
}
