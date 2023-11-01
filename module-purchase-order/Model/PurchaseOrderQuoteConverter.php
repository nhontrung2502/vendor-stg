<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartInterfaceFactory;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\ResourceModel\Quote\Item\Collection;

/**
 * Quote Serializer/Deserializer
 */
class PurchaseOrderQuoteConverter
{
    /**
     * @var CartInterfaceFactory
     */
    private $cartFactory;

    /**
     * @var ProductInterfaceFactory
     */
    private $productFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var CartItemInterfaceFactory
     */
    private $cartItemFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var CartManagementInterface
     */
    private $cartManagementInterface;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * PurchaseOrderQuoteConverter constructor.
     *
     * @param CartInterfaceFactory $cartFactory
     * @param CartItemInterfaceFactory $cartItemFactory
     * @param ProductInterfaceFactory $productFactory
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param CartManagementInterface $cartManagementInterface
     * @param UserContextInterface $userContext
     */
    public function __construct(
        CartInterfaceFactory $cartFactory,
        CartItemInterfaceFactory $cartItemFactory,
        ProductInterfaceFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        CartManagementInterface $cartManagementInterface,
        UserContextInterface $userContext
    ) {
        $this->cartFactory = $cartFactory;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->cartManagementInterface = $cartManagementInterface;
        $this->cartItemFactory = $cartItemFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->userContext = $userContext;
    }

    /**
     * Remove complex values from array
     *
     * @param array $array
     * @return array
     */
    private function removeComplexValuesFromArray(array $array)
    {
        foreach ($array as $key => $value) {
            if (is_array($value) || is_object($value)) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    /**
     * Convert quote object to array.
     *
     * @param CartInterface $quote
     * @return array
     */
    public function convertToArray(CartInterface $quote)
    {
        $serialized = [];

        $quoteData = $quote->getData();
        $serialized['quote'] = $this->removeComplexValuesFromArray($quoteData);

        if ($quote->getShippingAddress()) {
            $shippingAddressData = $quote->getShippingAddress()->getData();
            $serialized['shipping_address'] = $this->removeComplexValuesFromArray($shippingAddressData);
        }
        if ($quote->getBillingAddress()) {
            $billingAddressData = $quote->getBillingAddress()->getData();
            $serialized['billing_address'] = $this->removeComplexValuesFromArray($billingAddressData);
        }
        if ($quote->getPayment()) {
            $paymentData = $quote->getPayment()->getData();
            $serialized['payment'] = $this->removeComplexValuesFromArray($paymentData);
        }

        $serialized['items'] = [];
        foreach ($quote->getItemsCollection() as $item) {
            $itemData = $this->removeComplexValuesFromArray($item->getData());

            $itemOptions = [];
            foreach ($item->getOptions() as $option) {
                $itemOption = $this->removeComplexValuesFromArray($option->getData());
                $productData = $option->getProduct() ? $option->getProduct()->getData() : [];
                $itemOption['product'] = $this->removeComplexValuesFromArray($productData);
                $itemOptions[] = $itemOption;
            }
            $itemData['options'] = $itemOptions;
            $serialized['items'][] = $itemData;
        }
        return $serialized;
    }

    /**
     * Resolve item relations.
     *
     * @param Collection $itemsCollection
     * @return Collection
     */
    private function resolveItemRelations(Collection $itemsCollection)
    {
        /**
         * Assign parent items
         */
        foreach ($itemsCollection->getItems() as $item) {
            if ($item->getParentItemId()) {
                $item->setParentItem($itemsCollection->getItemById($item->getParentItemId()));
            }
        }

        return $itemsCollection;
    }

    /**
     * Convert array to quote object.
     *
     * @param array $serialized
     * @return CartInterface
     */
    public function convertArrayToQuote(array $serialized)
    {
        $quote = $this->cartFactory->create();
        $quote->setData($serialized['quote']);

        $quote->removeAllAddresses();

        if ($serialized['shipping_address']) {
            $serializedShippingAddress = $quote->getShippingAddress();
            $serializedShippingAddress->setData($serialized['shipping_address']);
        }
        if ($serialized['billing_address']) {
            $serializedBillingAddress = $quote->getBillingAddress();
            $serializedBillingAddress->setData($serialized['billing_address']);
        }
        if ($serialized['payment']) {
            $serializedPayment = $quote->getPayment();
            $serializedPayment->setData($serialized['payment']);
        }

        $quote->removeAllItems();
        $itemsCollection = $quote->getItemsCollection();
        $itemsCollection->removeAllItems();
        $itemNotFoundCount = 0;

        foreach ($serialized['items'] as $item) {
            if (!$this-> verifyProductExistsForQuoteItem($item)) {
                $itemNotFoundCount++;
                continue;
            }

            $options = $item['options'];
            unset($item['options']);

            /** @var QuoteItem $itemObject */
            $itemObject = $this->cartItemFactory->create();
            $itemObject->setData($item);
            $itemObject->setQuote($quote);

            foreach ($options as $option) {
                $productObject = $this->productFactory->create();
                $productObject->setData($option['product']);
                $option['product'] = $productObject;

                $itemObject->addOption(new DataObject($option));
            }

            // Explicitly set the quantity for this item so that it can be checked against available stock
            $itemObject->setQty($item['qty']);

            $itemsCollection->addItem($itemObject);
        }

        $this->resolveItemRelations($itemsCollection);

        $quote->setTotalsCollectedFlag($itemNotFoundCount === 0);

        return $quote;
    }

    /**
     * Verify that the quote item is associated to a product that exists in the database.
     *
     * @param array $quoteItem
     * @return bool
     */
    private function verifyProductExistsForQuoteItem(array $quoteItem)
    {
        try {
            $this->productRepository->getById((int) $quoteItem['product_id']);
        } catch (NoSuchEntityException $e) {
            return false;
        }

        return true;
    }
}
