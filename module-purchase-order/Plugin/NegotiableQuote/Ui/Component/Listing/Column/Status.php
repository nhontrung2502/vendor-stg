<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Plugin\NegotiableQuote\Ui\Component\Listing\Column;

use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\NegotiableQuote\Ui\Component\Listing\Column\Status as ColumnListingStatus;
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
     * Update status label to 'Purchase Order in Progress' if purchase order is being processed from a negotiable quote
     *
     * @param ColumnListingStatus $subject
     * @param array $dataSource
     * @return array
     */
    public function afterPrepareDataSource(ColumnListingStatus $subject, array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $subject->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                $originalStatusKey = $fieldName . '_original';

                if (!isset($item[$originalStatusKey])) {
                    continue;
                }

                $originalStatus = $item[$originalStatusKey];

                $isNegotiableQuoteAbleToBeCheckedOut = in_array(
                    $originalStatus,
                    $this->negotiableQuoteRestriction->getAllowedStatusesForAction(
                        RestrictionInterface::ACTION_PROCEED_TO_CHECKOUT
                    )
                );

                if (!$isNegotiableQuoteAbleToBeCheckedOut) {
                    continue;
                }

                $associatedPurchaseOrder = $this->purchaseOrderRepository->getByQuoteId($item['quote_id']);

                if ($associatedPurchaseOrder->getEntityId()) {
                    $item[$fieldName] = __('Purchase Order in Progress');
                }
            }
        }

        return $dataSource;
    }
}
