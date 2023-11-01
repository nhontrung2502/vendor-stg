<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrder\Block\PurchaseOrder\Info;

use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Block\PurchaseOrder\AbstractPurchaseOrder;
use Magento\PurchaseOrder\Model\Config\Source\Status as PurchaseOrderStatus;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Block class for the general status info section of the purchase order details page.
 *
 * @api
 * @since 100.2.0
 */
class Status extends AbstractPurchaseOrder
{
    /**
     * @var PurchaseOrderStatus
     */
    private $purchaseOrderStatus;

    /**
     * Status constructor.
     *
     * @param TemplateContext $context
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param PurchaseOrderStatus $purchaseOrderStatus
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        CartRepositoryInterface $quoteRepository,
        PurchaseOrderStatus $purchaseOrderStatus,
        array $data = []
    ) {
        parent::__construct($context, $purchaseOrderRepository, $quoteRepository, $data);
        $this->purchaseOrderStatus = $purchaseOrderStatus;
    }

    /**
     * Get the status label for the purchase order currently being viewed.
     *
     * @return string
     * @since 100.2.0
     */
    public function getStatusLabel()
    {
        $purchaseOrder = $this->getPurchaseOrder();
        $status = $purchaseOrder->getStatus();
        $statusLabel = '';

        if ($status) {
            $statusLabel = $this->purchaseOrderStatus->getLabelByStatus($status);
        }

        return $statusLabel;
    }
}
