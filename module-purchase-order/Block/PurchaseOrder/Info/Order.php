<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrder\Block\PurchaseOrder\Info;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Block\PurchaseOrder\AbstractPurchaseOrder;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Block class which provides associated sales order information for the purchase orders details page.
 *
 * @api
 * @since 100.2.0
 */
class Order extends AbstractPurchaseOrder
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @param TemplateContext $context
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        CartRepositoryInterface $quoteRepository,
        OrderRepositoryInterface $orderRepository,
        array $data = []
    ) {
        parent::__construct($context, $purchaseOrderRepository, $quoteRepository, $data);

        $this->orderRepository = $orderRepository;
    }

    /**
     * Get the associated order for the purchase order currently being viewed.
     *
     * @return OrderInterface|null
     * @throws NoSuchEntityException
     * @since 100.2.0
     */
    public function getOrder()
    {
        $purchaseOrder = $this->getPurchaseOrder();
        $orderId = $purchaseOrder->getOrderId();
        $order = null;

        if ($orderId) {
            $order = $this->orderRepository->get($orderId);
        }

        return $order;
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
}
