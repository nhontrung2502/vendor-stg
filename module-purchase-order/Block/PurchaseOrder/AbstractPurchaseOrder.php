<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Block\PurchaseOrder;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Purchase order block abstract class
 */
abstract class AbstractPurchaseOrder extends Template
{
    /**
     * @var PurchaseOrderInterface
     */
    private $purchaseOrder = null;

    /**
     * @var CartInterface
     */
    private $quote = null;

    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * AbstractPurchaseOrder constructor.
     *
     * @param TemplateContext $context
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        CartRepositoryInterface $quoteRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Get the Purchase Order currently being viewed.
     *
     * If not set, load the Purchase Order specified in the HTTP request by default.
     *
     * @return PurchaseOrderInterface
     * @throws NoSuchEntityException
     */
    public function getPurchaseOrder()
    {
        if ($this->purchaseOrder === null) {
            $purchaseOrderId = $this->_request->getParam('request_id');
            $this->setPurchaseOrderById($purchaseOrderId);
        }

        return $this->purchaseOrder;
    }

    /**
     * Set the Purchase Order for the block based on the provided Purchase Order id.
     *
     * @param int $purchaseOrderId
     * @return void
     * @throws NoSuchEntityException
     */
    public function setPurchaseOrderById($purchaseOrderId)
    {
        $this->purchaseOrder = $this->purchaseOrderRepository->getById($purchaseOrderId);
    }

    /**
     * Get the Quote for the Purchase Order currently being viewed.
     *
     * @return CartInterface|null
     */
    public function getQuote()
    {
        if ($this->quote === null) {
            $snapshotQuote = $this->getPurchaseOrder()->getSnapshotQuote();

            if ($snapshotQuote->getItemsCount()) {
                $this->quote = $snapshotQuote;
            } else {
                try {
                    $quoteId = $this->getPurchaseOrder()->getQuoteId();
                    $this->quote = $this->quoteRepository->get($quoteId, ['*']);
                } catch (NoSuchEntityException $e) {
                    $this->quote = null;
                }
            }
        }

        return $this->quote;
    }
}
