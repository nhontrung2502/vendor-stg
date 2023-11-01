<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Plugin\NegotiableQuote\Block\Quote;

use Magento\NegotiableQuote\Block\Quote\Info as BlockQuoteInfo;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;

class Info
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
     * Change status label to 'Purchase Order in Progress' if Purchase Order is being processed from Negotiable Quote
     *
     * @param BlockQuoteInfo $subject
     * @param string $statusLabel
     * @return string
     */
    public function afterGetQuoteStatusLabel(BlockQuoteInfo $subject, string $statusLabel)
    {
        $negotiableQuote = $subject->getQuote()->getExtensionAttributes()->getNegotiableQuote();

        $originalNegotiableQuoteStatus = $negotiableQuote->getData('status');

        $isNegotiableQuoteAbleToBeCheckedOut = in_array(
            $originalNegotiableQuoteStatus,
            $this->negotiableQuoteRestriction->getAllowedStatusesForAction(
                RestrictionInterface::ACTION_PROCEED_TO_CHECKOUT
            )
        );

        if ($isNegotiableQuoteAbleToBeCheckedOut) {
            $associatedPurchaseOrder = $this->purchaseOrderRepository->getByQuoteId($negotiableQuote->getQuoteId());

            if ($associatedPurchaseOrder->getEntityId()) {
                $statusLabel = (string) __('Purchase Order in Progress');
            }
        }

        return $statusLabel;
    }
}
