<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Customer\Authorization;

use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\CompanyContext;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Psr\Log\LoggerInterface;

/**
 * Cancel purchase order authorization class.
 */
class CancelPurchaseOrder implements ActionInterface
{
    /**
     * @var Structure
     */
    private $companyStructure;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CompanyContext
     */
    private $companyContext;

    /**
     * ApproveRejectPurchaseOrder constructor.
     * @param Structure $companyStructure
     * @param CompanyContext $companyContext
     * @param LoggerInterface $logger
     */
    public function __construct(
        Structure $companyStructure,
        CompanyContext $companyContext,
        LoggerInterface $logger
    ) {
        $this->companyStructure = $companyStructure;
        $this->logger = $logger;
        $this->companyContext = $companyContext;
    }

    /**
     * @inheritDoc
     */
    public function isAllowed(PurchaseOrderInterface $purchaseOrder): bool
    {
        try {
            return in_array(
                $purchaseOrder->getCreatorId(),
                $this->companyStructure->getAllowedChildrenIds((int)$this->companyContext->getCustomerId())
            ) || (int)$purchaseOrder->getCreatorId() === (int)$this->companyContext->getCustomerId();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            return false;
        }
    }
}
