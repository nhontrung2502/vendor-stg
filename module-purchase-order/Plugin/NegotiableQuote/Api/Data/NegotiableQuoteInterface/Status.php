<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Plugin\NegotiableQuote\Api\Data\NegotiableQuoteInterface;

use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;

class Status
{
    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * @var RestrictionInterface
     */
    private $negotiableQuoteRestriction;

    /**
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param RestrictionInterface $negotiableQuoteRestriction
     */
    public function __construct(
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        RestrictionInterface $negotiableQuoteRestriction
    ) {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->negotiableQuoteRestriction = $negotiableQuoteRestriction;
    }

    /**
     * Get negotiable quote status. If Purchase Order exists for the quote, return 'closed' status.
     *
     * @param NegotiableQuoteInterface $subject
     * @param string $negotiableQuoteStatus
     * @return string
     */
    public function afterGetStatus(NegotiableQuoteInterface $subject, $negotiableQuoteStatus)
    {
        if ($negotiableQuoteStatus === NegotiableQuoteInterface::STATUS_ORDERED) {
            return $negotiableQuoteStatus;
        }
        $isNegotiableQuoteAbleToBeCheckedOut = in_array(
            $negotiableQuoteStatus,
            $this->negotiableQuoteRestriction->getAllowedStatusesForAction(
                RestrictionInterface::ACTION_PROCEED_TO_CHECKOUT
            )
        );

        if ($isNegotiableQuoteAbleToBeCheckedOut) {
            $associatedPurchaseOrder = $this->purchaseOrderRepository->getByQuoteId($subject->getQuoteId());

            // If Purchase Order exists and Negotiable Quote can still be checked out, assign a ordered status
            if ($associatedPurchaseOrder->getEntityId()) {
                $negotiableQuoteStatus = NegotiableQuoteInterface::STATUS_ORDERED;
            }
        }

        return $negotiableQuoteStatus;
    }
}
