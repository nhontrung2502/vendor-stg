<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Block\PurchaseOrder\Info;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Block\PurchaseOrder\AbstractPurchaseOrder;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\PurchaseOrder\Model\Customer\Authorization;
use Magento\PurchaseOrder\Model\Payment\DeferredPaymentStrategy;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Block class for payment details on the purchase order details page.
 * @api
 */
class Banner extends AbstractPurchaseOrder
{
    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var DeferredPaymentStrategy
     */
    private $deferredPaymentStrategy;

    /**
     * @var Authorization
     */
    private $authorization;

    /**
     * Banner constructor.
     *
     * @param TemplateContext $context
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param CustomerSession $customerSession
     * @param DeferredPaymentStrategy $deferredPaymentStrategy
     * @param Authorization $authorization
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        CartRepositoryInterface $quoteRepository,
        CustomerSession $customerSession,
        DeferredPaymentStrategy $deferredPaymentStrategy,
        Authorization $authorization,
        array $data = []
    ) {
        parent::__construct($context, $purchaseOrderRepository, $quoteRepository, $data);
        $this->customerSession = $customerSession;
        $this->deferredPaymentStrategy = $deferredPaymentStrategy;
        $this->authorization = $authorization;
    }

    /**
     * Check if current customer can place order using online payment
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function canOrder(): bool
    {
        $purchaseOrder = $this->getPurchaseOrder();
        $allowedStatuses = [
            PurchaseOrderInterface::STATUS_APPROVED_PENDING_PAYMENT,
            PurchaseOrderInterface::STATUS_ORDER_FAILED
        ];

        if (in_array($purchaseOrder->getStatus(), $allowedStatuses) &&
            $this->deferredPaymentStrategy->isDeferredPayment($purchaseOrder) &&
            $this->isCreator() &&
            !$this->hasError()
        ) {
            return true;
        }
        return false;
    }

    /**
     * Check if purchase order has error
     *
     * @return bool
     */
    public function hasError(): bool
    {
        $quote = $this->getQuote();
        if (!$quote->hasItems() || $quote->getHasError() || !$quote->validateMinimumAmount()) {
            return true;
        }
        return false;
    }

    /**
     * Check if current customer can see the banner
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function canView(): bool
    {
        return $this->getPurchaseOrder()->getStatus() === PurchaseOrderInterface::STATUS_APPROVED_PENDING_PAYMENT &&
            $this->deferredPaymentStrategy->isDeferredPayment($this->getPurchaseOrder()) &&
            $this->authorization->isAllowed('View', $this->getPurchaseOrder());
    }

    /**
     * Check if current customer is purchase order creator
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    private function isCreator(): bool
    {
        $creatorId = $this->getPurchaseOrder()->getCreatorId();
        $currentCustomerId = $this->customerSession->getCustomerId();

        return ((int) $creatorId) === ((int) $currentCustomerId);
    }
}
