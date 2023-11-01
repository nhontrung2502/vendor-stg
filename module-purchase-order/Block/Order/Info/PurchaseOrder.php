<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Block\Order\Info;

use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Block for displaying link to purchase order on sales order.
 *
 * @api
 * @since 100.2.0
 */
class PurchaseOrder extends \Magento\Framework\View\Element\Template
{
    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * @var \Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface
     */
    private $purchaseOrder;

    /**
     * PurchaseOrder constructor.
     *
     * @param TemplateContext $context
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->purchaseOrderRepository = $purchaseOrderRepository;
    }

    /**
     * Is order created from purchase order.
     *
     * @return bool
     * @since 100.2.0
     */
    public function isFromPurchaseOrder() : bool
    {
        return $this->getLinkedPurchaseOrder() instanceof PurchaseOrderInterface;
    }

    /**
     * Get purchase order for order. Returns null if no purchase order linked.
     *
     * @return PurchaseOrderInterface|null
     * @since 100.2.0
     */
    public function getLinkedPurchaseOrder() : ?PurchaseOrderInterface
    {
        if (!$this->purchaseOrder) {
            $this->purchaseOrder = $this->purchaseOrderRepository->getByOrderId(
                $this->getCurrentOrder()->getEntityId()
            );
        }
        return $this->purchaseOrder->getEntityId() ? $this->purchaseOrder : null;
    }

    /**
     * Get order instance for current purchase order.
     *
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    private function getCurrentOrder() : OrderInterface
    {
        // avoiding direct reference to registry
        /** @var \Magento\Sales\Block\Order\View $statusBlock */
        $statusBlock = $this->_layout->getBlock('sales.order.view');
        return $statusBlock->getOrder();
    }

    /**
     * Get URL for purchase order view.
     *
     * @param int $purchaseOrderId
     * @return string
     * @since 100.2.0
     */
    public function getPurchaseOrderUrl($purchaseOrderId) : string
    {
        return $this->getUrl(
            'purchaseorder/purchaseorder/view',
            [
                'request_id' => $purchaseOrderId
            ]
        );
    }
}
