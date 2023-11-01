<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Processor;

use Magento\Company\Model\Company\Structure as CompanyStructure;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderManagementInterface;

/**
 * Automatically approve purchase order if approver is manager (i.e. above purchase order creator in company structure)
 */
class ManagerApprovalProcessor implements ApprovalProcessorInterface
{
    /**
     * @var PurchaseOrderManagementInterface
     */
    private $purchaseOrderManagement;

    /**
     * @var CompanyStructure
     */
    private $companyStructure;

    /**
     * @param CompanyStructure $companyStructure
     * @param PurchaseOrderManagementInterface $purchaseOrderManagement
     */
    public function __construct(
        CompanyStructure $companyStructure,
        PurchaseOrderManagementInterface $purchaseOrderManagement
    ) {
        $this->purchaseOrderManagement = $purchaseOrderManagement;
        $this->companyStructure = $companyStructure;
    }

    /**
     * @inheritDoc
     */
    public function processApproval(PurchaseOrderInterface $purchaseOrder, int $customerId)
    {
        if (in_array($purchaseOrder->getCreatorId(), $this->companyStructure->getAllowedChildrenIds($customerId))) {
            $this->purchaseOrderManagement->approvePurchaseOrder($purchaseOrder, $customerId);
        }
    }
}
