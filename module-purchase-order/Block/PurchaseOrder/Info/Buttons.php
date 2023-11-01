<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Block\PurchaseOrder\Info;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Block\PurchaseOrder\AbstractPurchaseOrder;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\PurchaseOrder\Model\Customer\Authorization;
use Magento\PurchaseOrder\Model\Payment\DeferredPaymentStrategy;
use Magento\PurchaseOrder\Model\Validator\ActionReady\ValidatorLocator;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Block class for the action buttons on the purchase order details page.
 *
 * @api
 * @since 100.2.0
 */
class Buttons extends AbstractPurchaseOrder
{
    /**
     * @var Authorization
     */
    private $authorization;

    /**
     * @var ValidatorLocator
     */
    private $validatorLocator;

    /**
     * @var DeferredPaymentStrategy
     */
    private $deferredPaymentStrategy;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @param TemplateContext $context
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param Authorization $authorization
     * @param ValidatorLocator $validatorLocator
     * @param array $data
     * @param DeferredPaymentStrategy|null $deferredPaymentStrategy
     * @param CustomerSession|null $customerSession
     */
    public function __construct(
        TemplateContext $context,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        CartRepositoryInterface $quoteRepository,
        Authorization $authorization,
        ValidatorLocator $validatorLocator,
        array $data = [],
        DeferredPaymentStrategy $deferredPaymentStrategy = null,
        CustomerSession $customerSession = null
    ) {
        parent::__construct($context, $purchaseOrderRepository, $quoteRepository, $data);
        $this->authorization = $authorization;
        $this->validatorLocator = $validatorLocator;
        $this->deferredPaymentStrategy = $deferredPaymentStrategy
            ?? ObjectManager::getInstance()->get(DeferredPaymentStrategy::class);
        $this->customerSession = $customerSession ?? ObjectManager::getInstance()->get(CustomerSession::class);
    }

    /**
     * Get the url to reject the currently viewed purchase order.
     *
     * @return string
     * @since 100.2.0
     */
    public function getRejectUrl()
    {
        return $this->getUrl(
            'purchaseorder/purchaseorder/reject',
            ['request_id' => $this->_request->getParam('request_id')]
        );
    }

    /**
     * Check is action allowed on current purchase order.
     *
     * @param string $action
     * @return bool
     * @throws NoSuchEntityException
     */
    private function isAllowedAction(string $action) : bool
    {
        return $this->validatorLocator->getValidator($action)->validate($this->getPurchaseOrder())
            && $this->authorization->isAllowed($action, $this->getPurchaseOrder());
    }

    /**
     * Checks if the currently viewed purchase order can be rejected.
     *
     * @return bool
     * @throws LocalizedException
     * @since 100.2.0
     */
    public function canReject() : bool
    {
        return $this->isAllowedAction('reject');
    }

    /**
     * Get the url to approve the currently viewed purchase order.
     *
     * @return string
     * @since 100.2.0
     */
    public function getApproveUrl()
    {
        return $this->getUrl(
            'purchaseorder/purchaseorder/approve',
            ['request_id' => $this->_request->getParam('request_id')]
        );
    }

    /**
     * Checks if the currently viewed purchase order can be approved.
     *
     * @return bool
     * @throws NoSuchEntityException
     * @since 100.2.0
     */
    public function canApprove() : bool
    {
        return $this->isAllowedAction('approve');
    }

    /**
     * Gets the url to cancel the currently viewed purchase order.
     *
     * @return string
     * @since 100.2.0
     */
    public function getCancelUrl() : string
    {
        return $this->getUrl(
            'purchaseorder/purchaseorder/cancel',
            ['request_id' => $this->_request->getParam('request_id')]
        );
    }

    /**
     * Checks if the currently viewed purchase order can be deleted.
     *
     * @return bool
     * @throws NoSuchEntityException
     * @since 100.2.0
     */
    public function canCancel() : bool
    {
        return $this->isAllowedAction('cancel');
    }

    /**
     * Gets the url to convert the currently viewed purchase order to a sales order.
     *
     * @return string
     * @since 100.2.0
     */
    public function getPlaceOrderUrl()
    {
        return $this->getUrl(
            'purchaseorder/purchaseorder/placeorder',
            ['request_id' => $this->_request->getParam('request_id')]
        );
    }

    /**
     * Checks if the currently viewed purchase order can be converted to a sales order.
     *
     * @return bool
     * @throws NoSuchEntityException
     * @since 100.2.0
     */
    public function canOrder() : bool
    {
        return $this->isAllowedAction('placeorder') ||
            ($this->paymentRequired() && !$this->hasError());
    }

    /**
     * Check if the purchase order requires payment from current customer
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function paymentRequired(): bool
    {
        $purchaseOrder = $this->getPurchaseOrder();
        $poStatus = $purchaseOrder->getStatus();

        if ($poStatus === PurchaseOrderInterface::STATUS_APPROVED_PENDING_PAYMENT &&
            $this->deferredPaymentStrategy->isDeferredPayment($purchaseOrder) &&
            $this->isCreator()
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
