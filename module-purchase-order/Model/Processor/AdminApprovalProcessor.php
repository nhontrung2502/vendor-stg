<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Processor;

use Magento\Company\Model\CompanyAdminPermission;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderManagementInterface;

/**
 * Automatically approve purchase order if approver is company admin
 */
class AdminApprovalProcessor implements ApprovalProcessorInterface
{
    /**
     * @var CompanyAdminPermission
     */
    private $companyAdminPermission;

    /**
     * @var PurchaseOrderManagementInterface
     */
    private $purchaseOrderManagement;

    /**
     * @param CompanyAdminPermission $companyAdminPermission
     * @param PurchaseOrderManagementInterface $purchaseOrderManagement
     */
    public function __construct(
        CompanyAdminPermission $companyAdminPermission,
        PurchaseOrderManagementInterface $purchaseOrderManagement
    ) {
        $this->companyAdminPermission = $companyAdminPermission;
        $this->purchaseOrderManagement = $purchaseOrderManagement;
    }

    /**
     * @inheritDoc
     */
    public function processApproval(PurchaseOrderInterface $purchaseOrder, int $customerId)
    {
        if ($customerId == null ||
            $this->companyAdminPermission->isGivenUserCompanyAdmin($customerId)
        ) {
            $this->purchaseOrderManagement->approvePurchaseOrder($purchaseOrder, $customerId);
        }
    }
}
