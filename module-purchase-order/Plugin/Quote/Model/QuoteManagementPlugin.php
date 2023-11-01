<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Plugin\Quote\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Model\Quote\History as NegotiableQuoteHistory;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Model\PurchaseOrder\LogManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Plugin class for QuoteManagement related to purchase orders.
 *
 * @see \Magento\Quote\Model\QuoteManagement
 */
class QuoteManagementPlugin
{
    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * @var LogManagementInterface
     */
    private $purchaseOrderLogManagement;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var NegotiableQuoteHistory
     */
    private $negotiableQuoteHistory;

    /**
     * Plugin constructor.
     *
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param LogManagementInterface $purchaseOrderLogManagement
     * @param CartRepositoryInterface $quoteRepository
     * @param NegotiableQuoteHistory $negotiableQuoteHistory
     */
    public function __construct(
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        LogManagementInterface $purchaseOrderLogManagement,
        CartRepositoryInterface $quoteRepository,
        NegotiableQuoteHistory $negotiableQuoteHistory
    ) {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->purchaseOrderLogManagement = $purchaseOrderLogManagement;
        $this->quoteRepository = $quoteRepository;
        $this->negotiableQuoteHistory = $negotiableQuoteHistory;
    }

    /**
     * Performs additional actions after order creation if the order originated from a purchase order quote.
     *
     * @param QuoteManagement $subject
     * @param AbstractExtensibleModel|OrderInterface|object|null $result
     * @param Quote $quote
     * @return AbstractExtensibleModel|OrderInterface|object|null
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSubmit(
        QuoteManagement $subject,
        $result,
        Quote $quote
    ) {
        $purchaseOrder = $this->purchaseOrderRepository->getByQuoteId($quote->getId());
        if ($result && $purchaseOrder->getEntityId()) {
            $this->restoreAppliedRulesToOrder($quote, $result);

            // If the purchase order was pending deferred payment, link it to the order created at the final checkout
            if ($purchaseOrder->getStatus() === PurchaseOrderInterface::STATUS_APPROVED_PENDING_PAYMENT) {
                $this->linkPurchaseOrderToOrder($purchaseOrder, $result);
                $this->updateNegotiableQuote($quote);
            }
        }

        return $result;
    }

    /**
     * Set negotiable quote to ORDERED status if linked to quote.
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     */
    private function updateNegotiableQuote(\Magento\Quote\Api\Data\CartInterface $quote)
    {
        /** @var NegotiableQuoteInterface $negotiableQuote */
        $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();

        if ($negotiableQuote !== null && $negotiableQuote->getQuoteId() !== null) {
            $negotiableQuote->setStatus(NegotiableQuoteInterface::STATUS_ORDERED);
            $this->quoteRepository->save($quote);
            $this->negotiableQuoteHistory->updateLog($negotiableQuote->getQuoteId());
        }
    }

    /**
     * Restore the order applied rules from the purchase order quote.
     *
     * @param Quote $quote
     * @param OrderInterface $order
     */
    private function restoreAppliedRulesToOrder(Quote $quote, OrderInterface $order)
    {
        if ($quote->getAppliedRuleIds() === null) {
            $order->setAppliedRuleIds('');
        } else {
            $order->setAppliedRuleIds($quote->getAppliedRuleIds());
        }
    }

    /**
     * Link the purchase order to the newly created order.
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @param OrderInterface $order
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     */
    private function linkPurchaseOrderToOrder(PurchaseOrderInterface $purchaseOrder, OrderInterface $order)
    {
        $purchaseOrder->setOrderId($order->getId());
        $purchaseOrder->setOrderIncrementId($order->getIncrementId());
        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_ORDER_PLACED);
        $this->purchaseOrderRepository->save($purchaseOrder);
        $this->purchaseOrderLogManagement->logAction(
            $purchaseOrder,
            'place_order',
            [
                'increment_id' => $purchaseOrder->getIncrementId(),
                'order_increment_id' => $order->getIncrementId()
            ],
            $order->getCustomerId()
        );
    }
}
