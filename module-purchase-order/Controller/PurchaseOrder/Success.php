<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Controller\PurchaseOrder;

use Magento\Checkout\Model\Type\Onepage;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Checkout Purchase Order Creation Success Controller Action
 */
class Success extends Action implements HttpGetActionInterface
{
    /**
     * @var Onepage
     */
    private $onePage;

    /**
     * @param Context $context
     * @param Onepage $onePage
     */
    public function __construct(
        Context $context,
        Onepage $onePage
    ) {
        parent::__construct($context);
        $this->onePage = $onePage;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $checkoutSession = $this->onePage->getCheckout();
        $purchaseOrderId = $checkoutSession->getCurrentPurchaseOrderId();

        if (!$purchaseOrderId) {
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }

        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        return $resultPage;
    }
}
