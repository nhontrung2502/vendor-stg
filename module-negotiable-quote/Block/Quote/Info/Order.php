<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Block\Quote\Info;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\View\Element\Template;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Class provides quote order information.
 *
 * @api
 * @since 100.2.0
 */
class Order extends Template
{
    /**
     * @var \Magento\NegotiableQuote\Helper\Quote
     */
    private $negotiableQuoteHelper;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param Template\Context $context
     * @param \Magento\NegotiableQuote\Helper\Quote $negotiableQuoteHelper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepositoryInterface $orderRepository
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\NegotiableQuote\Helper\Quote $negotiableQuoteHelper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRepositoryInterface $orderRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->negotiableQuoteHelper = $negotiableQuoteHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Get the url for the specified order.
     *
     * @param int $orderId
     * @return string
     * @since 100.2.0
     */
    public function getOrderUrl($orderId)
    {
        return $this->getUrl('sales/order/view', ['order_id' => $orderId]);
    }

    /**
     * Get the associated order for the negotiable quote currently being viewed.
     *
     * @return OrderInterface|null
     * @since 100.2.0
     */
    public function getOrder()
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(OrderInterface::QUOTE_ID, $this->getQuote()->getId())
            ->create();

        $orders = $this->orderRepository->getList($searchCriteria)
            ->getItems();

        return array_pop($orders);
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
