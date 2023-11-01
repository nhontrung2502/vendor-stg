<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Customer\Authorization;

use Magento\Company\Model\CompanyContext;
use Magento\Company\Model\CompanyManagement;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\ResourceModel\Customer as CustomerResource;
use Magento\Framework\Exception\LocalizedException;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;

/**
 * View purchase order action authorization class.
 */
class ViewPurchaseOrder implements ActionInterface
{
    /**
     * @var CompanyContext
     */
    private $companyContext;

    /**
     * @var CompanyManagement
     */
    private $companyManagement;

    /**
     * @var Structure
     */
    private $companyStructure;

    /**
     * @var CustomerResource
     */
    private $customerResource;

    /**
     * @param CompanyContext $companyContext
     * @param CompanyManagement $companyManagement
     * @param Structure $companyStructure
     * @param CustomerResource $customerResource
     */
    public function __construct(
        CompanyContext $companyContext,
        CompanyManagement $companyManagement,
        Structure $companyStructure,
        CustomerResource $customerResource
    ) {
        $this->companyContext = $companyContext;
        $this->companyManagement = $companyManagement;
        $this->companyStructure = $companyStructure;
        $this->customerResource = $customerResource;
    }

    /**
     * @inheritDoc
     */
    public function isAllowed(PurchaseOrderInterface $purchaseOrder) : bool
    {
        $purchaseOrderCompanyId = $purchaseOrder->getCompanyId();
        $currentCustomerId = $this->companyContext->getCustomerId();
        $currentCustomerCompanyId = $this->companyManagement->getByCustomerId($currentCustomerId)->getId();
        if ($purchaseOrderCompanyId != $currentCustomerCompanyId
            || !$this->companyContext->isResourceAllowed('Magento_PurchaseOrder::view_purchase_orders')
        ) {
            return false;
        }

        $purchaseOrderCreatorId = $purchaseOrder->getCreatorId();
        if (((int) $purchaseOrderCreatorId) === ((int) $currentCustomerId)) {
            return true;
        } else {
            try {
                return in_array(
                    $purchaseOrderCreatorId,
                    $this->getAllowedPurchaseOrderCreatorIds($currentCustomerCompanyId, $currentCustomerId)
                );
            } catch (LocalizedException $exception) {
                return false;
            }
        }
    }

    /**
     * Get the creator Ids whose purchase order is allowed to be viewed by the current customer
     *
     * @param int $companyId
     * @param int $customerId
     * @return array
     */
    private function getAllowedPurchaseOrderCreatorIds($companyId, $customerId)
    {
        $allowedCustomerIds = [];
        if ($this->companyContext->isResourceAllowed('Magento_PurchaseOrder::view_purchase_orders')) {
            $allowedCustomerIds[] = $customerId;

            if ($this->companyContext->isResourceAllowed(
                'Magento_PurchaseOrder::view_purchase_orders_for_subordinates'
            )) {
                $subordinates = $this->companyStructure->getAllowedChildrenIds($customerId);
                $allowedCustomerIds = array_merge($allowedCustomerIds, $subordinates);
            }

            if ($this->companyContext->isResourceAllowed(
                'Magento_PurchaseOrder::view_purchase_orders_for_company'
            )) {
                $companyCustomers = $this->customerResource->getCustomerIdsByCompanyId($companyId);
                $allowedCustomerIds = array_merge($allowedCustomerIds, $companyCustomers);
            }
        }
        $allowedCustomerIds = array_unique($allowedCustomerIds);
        return $allowedCustomerIds;
    }
}
