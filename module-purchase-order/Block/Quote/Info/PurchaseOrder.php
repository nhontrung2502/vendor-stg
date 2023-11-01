<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PurchaseOrder\Block\Quote\Info;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\View\Element\Template;
use Magento\NegotiableQuote\Helper\Quote as NegotiableQuoteHelper;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Block class which provides associated purchase order information for the quote details page.
 *
 * @api
 * @since 100.2.0
 */
class PurchaseOrder extends Template
{
    /**
     * @var NegotiableQuoteHelper
     */
    private $negotiableQuoteHelper;

    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param Template\Context $context
     * @param NegotiableQuoteHelper $negotiableQuoteHelper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        NegotiableQuoteHelper $negotiableQuoteHelper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->negotiableQuoteHelper = $negotiableQuoteHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
    }

    /**
     * Get the url for the specified purchase order.
     *
     * @param int $purchaseOrderId
     * @return string
     * @since 100.2.0
     */
    public function getPurchaseOrderUrl($purchaseOrderId)
    {
        return $this->getUrl('purchaseorder/purchaseorder/view', ['request_id' => $purchaseOrderId]);
    }

    /**
     * Get the associated purchase order for the negotiable quote currently being viewed.
     *
     * @return PurchaseOrderInterface|null
     * @since 100.2.0
     */
    public function getPurchaseOrder()
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            PurchaseOrderInterface::QUOTE_ID,
            $this->getQuote()->getId()
        )->create();

        $purchaseOrders = $this->purchaseOrderRepository->getList($searchCriteria)->getItems();

        return array_pop($purchaseOrders);
    }

    /**
     * Retrieve current quote.
     *
     * @return CartInterface|null
     */
    private function getQuote()
    {
        return $this->negotiableQuoteHelper->resolveCurrentQuote(false);
    }
}
