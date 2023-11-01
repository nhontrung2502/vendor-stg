<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Block\PurchaseOrder;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;

/**
 * Block for Purchase Order Success Landing Page
 *
 * @api
 * @since 100.2.0
 */
class Success extends Template
{
    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @param TemplateContext $context
     * @param CheckoutSession $checkoutSession
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        CheckoutSession $checkoutSession,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->_isScopePrivate = true;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
    }

    /**
     * Get Current Purchase Order Id
     *
     * @return int
     * @since 100.2.0
     */
    public function getPurchaseOrderId()
    {
        return $this->checkoutSession->getCurrentPurchaseOrderId();
    }

    /**
     * Get Purchase Order
     *
     * @return PurchaseOrderInterface
     * @throws NoSuchEntityException
     * @since 100.2.0
     */
    public function getPurchaseOrder()
    {
        return $this->purchaseOrderRepository->getById($this->getPurchaseOrderId());
    }

    /**
     * Get Purchase Order URL
     *
     * @return string
     * @since 100.2.0
     */
    public function getPurchaseOrderUrl()
    {
        return $this->getUrl('purchaseorder/purchaseorder/view', ['request_id' => $this->getPurchaseOrderId()]);
    }

    /**
     * Get Continue Shopping URL
     *
     * @return string
     * @throws NoSuchEntityException
     * @since 100.2.0
     */
    public function getContinueUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    /**
     * Set Title
     *
     * @return $this
     * @since 100.2.0
     */
    protected function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__('Your Purchase Order has been submitted for approval.'));

        return parent::_prepareLayout();
    }
}
