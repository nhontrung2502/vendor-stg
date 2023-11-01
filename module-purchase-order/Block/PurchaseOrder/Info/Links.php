<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Block\PurchaseOrder\Info;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Block\PurchaseOrder\AbstractPurchaseOrder;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Block class for the available action links on the purchase order details page.
 *
 * @api
 * @since 100.2.0
 */
class Links extends AbstractPurchaseOrder
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param TemplateContext $context
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param SerializerInterface $serializer
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        CartRepositoryInterface $quoteRepository,
        SerializerInterface $serializer,
        array $data = []
    ) {
        parent::__construct($context, $purchaseOrderRepository, $quoteRepository, $data);
        $this->serializer = $serializer;
    }

    /**
     * Get add purchase order items to shopping cart post data.
     *
     * @return string
     * @since 100.2.0
     */
    public function getAddItemPostData() : string
    {
        $url = $this->getUrl(
            'purchaseorder/purchaseorder/addItem',
            ['request_id' => $this->getPurchaseOrder()->getId()]
        );
        return $this->serializer->serialize(['action' => $url, 'data' => []]);
    }
}
