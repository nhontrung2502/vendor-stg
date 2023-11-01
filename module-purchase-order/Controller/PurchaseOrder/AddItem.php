<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Controller\PurchaseOrder;

use Magento\Checkout\Model\Cart;
use Magento\Framework\App\Action\Context;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\CartFactory;
use Magento\Company\Model\CompanyContext;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\StoreManagerInterface;
use Magento\PurchaseOrder\Model\Customer\Authorization;

/**
 * Add purchase order items to shopping cart controller
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AddItem extends \Magento\PurchaseOrder\Controller\AbstractController implements HttpPostActionInterface
{
    /**
     * @var CartFactory
     */
    private $cartFactory;

    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param Context $context
     * @param CompanyContext $companyContext
     * @param Authorization $authorization
     * @param CartFactory $cartFactory
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Context $context,
        CompanyContext $companyContext,
        Authorization $authorization,
        CartFactory $cartFactory,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository
    ) {
        parent::__construct($context, $companyContext, $authorization);
        $this->cartFactory = $cartFactory;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
    }

    /**
     * Execute action add purchase order items to shopping cart, replace the existing shopping cart items if requested
     *
     * @return Redirect
     */
    public function execute() : Redirect
    {
        $requestId = (int)$this->getRequest()->getParam('request_id');
        $replaceCart = $this->getRequest()->getParam('replace_cart') == 1 ? true : false;

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        try {
            $purchaseOrder = $this->purchaseOrderRepository->getById($requestId);
            $quote = $purchaseOrder->getSnapshotQuote();
            $items = $quote->getAllVisibleItems();
            $cart = $this->cartFactory->create();
            if ($replaceCart) {
                $cart->truncate();
            }
            $this->addItemsToCart($items, $cart);
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }
        return $resultRedirect->setPath("checkout/cart");
    }

    /**
     * Add purchase order items to shopping cart
     *
     * @param Item[] $items
     * @param Cart $cart
     * @return void
     */
    private function addItemsToCart($items, $cart) : void
    {
        $notSaleableItemCount = 0;
        foreach ($items as $item) {
            if ($item->getParentItem() === null) {
                $product = $this->getItemProduct($item);
                if ($product == null) {
                    $notSaleableItemCount++;
                    continue;
                }
                $options = $item->getProduct()->getTypeInstance()->getOrderOptions($item->getProduct());
                $info = isset($options['info_buyRequest']) ? $options['info_buyRequest'] : [];
                $info = new \Magento\Framework\DataObject($info);
                $info->setQty($item->getQty());
                $cart->addProduct($product, $info);
            }
        }
        if ($notSaleableItemCount > 0) {
            $this->messageManager->addErrorMessage(
                __(
                    "Some Item(s) are not available and are not added into the shopping cart."
                )
            );
            if (count($items) == $notSaleableItemCount) {
                return;
            }
        }
        $cart->save();
    }

    /**
     * Get product from the given quote item
     *
     * @param Item $item
     * @return ProductInterface|null
     */
    private function getItemProduct(Item $item) : ?ProductInterface
    {
        $storeId = $this->storeManager->getStore()->getId();

        try {
            /**
             * We need to reload product in this place, because products
             * with the same id may have different sets of order attributes.
             */
            $product = $this->productRepository->getById(
                $item->getProductId(),
                false,
                $storeId,
                true
            );
            if ($product->getTypeId() == "configurable") {
                $childProduct = $this->productRepository->get($item->getSku());
                if (!$childProduct->isSaleable()) {
                    return null;
                }
            }
            if (!$product->isSaleable()) {
                return null;
            }
        } catch (NoSuchEntityException $e) {
            return null;
        }
        return $product;
    }

    /**
     * Check if this action is allowed.
     *
     * Verify that the user belongs to a company with purchase orders enabled.
     * Verify that the user can view the purchase order from the request.
     *
     * @return bool
     * @throws LocalizedException
     */
    protected function isAllowed() : bool
    {
        $purchaseOrderId = $this->_request->getParam('request_id');
        try {
            $purchaseOrder = $this->purchaseOrderRepository->getById($purchaseOrderId);
        } catch (NoSuchEntityException $exception) {
            return false;
        }
        return parent::isAllowed()
            && $this->purchaseOrderActionAuth->isAllowed('View', $purchaseOrder);
    }
}
