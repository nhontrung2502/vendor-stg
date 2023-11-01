<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Model\Customer;

use Magento\Customer\Model\ResourceModel\GroupRepository as CustomerGroupRepository;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\Config as TaxConfig;

/**
 * Class for Tax Rate conversion between store and customer rates
 */
class StoreCustomerRate
{
    /**
     * @var Calculation
     */
    private $calculationTool;

    /**
     * @var CustomerGroupRepository
     */
    private $customerGroupRepository;

    /**
     * @var CartInterface
     */
    private $quote;

    /**
     * @var TaxConfig
     */
    private $taxConfig;

    /**
     * @var DataObject
     */
    private $taxRateRequest;

    /**
     * @param Calculation $calculationTool
     * @param CustomerGroupRepository $customerGroupRepository
     * @param TaxConfig $taxConfig
     */
    public function __construct(
        Calculation $calculationTool,
        CustomerGroupRepository $customerGroupRepository,
        TaxConfig $taxConfig
    ) {
        $this->calculationTool = $calculationTool;
        $this->customerGroupRepository = $customerGroupRepository;
        $this->taxConfig = $taxConfig;
    }

    /**
     * Inits the quote.
     *
     * @param CartInterface $quote
     */
    public function init(CartInterface $quote)
    {
        $this->quote = $quote;
        $this->taxRateRequest = null;
    }

    /**
     * Returns tax rate factor.
     *
     * @param CartItemInterface $cartItem
     * @return float
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getTaxRateFactor(CartItemInterface $cartItem): float
    {
        $taxRateFactor = 1.0;

        if ($this->isTaxFactorApplicable((int)$cartItem->getStoreId())) {
            $customerRate = $this->getCustomerRate($cartItem);
            $storeRate = $this->getStoreRate($cartItem);

            $taxRateFactor = (100 + $storeRate - $customerRate) / 100;
        }

        return $taxRateFactor;
    }

    /**
     * Returns customer tax rate factor.
     *
     * @param CartItemInterface $cartItem
     * @return float
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCustomerTaxRateFactor(CartItemInterface $cartItem): float
    {
        $taxRateFactor = 1.0;

        if ($this->isTaxFactorApplicable((int)$cartItem->getStoreId())) {
            $customerRate = $this->getCustomerRate($cartItem);

            $taxRateFactor = (100 + $customerRate) / 100;
        }

        return $taxRateFactor;
    }

    /**
     * Returns store tax rate factor.
     *
     * @param CartItemInterface $cartItem
     * @return float
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getStoreTaxRateFactor(CartItemInterface $cartItem): float
    {
        $taxRateFactor = 1.0;
        if ($this->isTaxFactorApplicable((int)$cartItem->getStoreId())) {
            $storeRate = $this->getStoreRate($cartItem);

            $taxRateFactor = (100 + $storeRate) / 100;
        }

        return $taxRateFactor;
    }

    /**
     * Checks whether tax factor calculation is applicable.
     *
     * @param int $storeId
     * @return bool
     */
    private function isTaxFactorApplicable(int $storeId): bool
    {
        return $this->hasNegotiatedPrice()
            && $this->taxConfig->priceIncludesTax($storeId)
            && !$this->taxConfig->crossBorderTradeEnabled($storeId);
    }

    /**
     * Checks negotiated price.
     *
     * @return bool
     */
    private function hasNegotiatedPrice(): bool
    {
        $hasNegotiatedPrice = false;
        if ($this->quote &&
            $this->quote->getExtensionAttributes() &&
            $this->quote->getExtensionAttributes()->getNegotiableQuote()
        ) {
            $negotiableQuote = $this->quote->getExtensionAttributes()->getNegotiableQuote();
            $hasNegotiatedPrice = $negotiableQuote->getNegotiatedTotalPrice() !== null;
        }

        return $hasNegotiatedPrice;
    }

    /**
     * Returns customer rate.
     *
     * @param CartItemInterface $cartItem
     * @return float
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getCustomerRate(CartItemInterface $cartItem): float
    {
        $taxRateRequest = $this->getQuoteTaxRateRequest();
        $product = $cartItem->getProduct();
        $taxRateRequest->setData('product_class_id', $product ? $product->getTaxClassId() : null);
        return (float) $this->calculationTool->getRate($taxRateRequest);
    }

    /**
     * Returns store rate.
     *
     * @param CartItemInterface $cartItem
     * @return float
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getStoreRate(CartItemInterface $cartItem): float
    {
        $taxRateRequest = $this->getQuoteTaxRateRequest();
        $product = $cartItem->getProduct();
        $taxRateRequest->setData('product_class_id', $product ? $product->getTaxClassId() : null);
        return (float) $this->calculationTool->getStoreRate($taxRateRequest, $this->quote->getStoreId());
    }

    /**
     * Returns quote tax rate request.
     *
     * @return DataObject
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getQuoteTaxRateRequest(): DataObject
    {
        if (!$this->taxRateRequest) {
            $this->taxRateRequest = $this->calculationTool->getRateRequest(
                $this->quote->getShippingAddress(),
                $this->quote->getBillingAddress(),
                $this->getCustomerTaxClassId(),
                $this->quote->getStoreId(),
                $this->quote->getCustomer()->getId()
            );
        }

        return $this->taxRateRequest;
    }

    /**
     * Returns customer tax class.
     *
     * @return int
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getCustomerTaxClassId(): int
    {
        $customerGroupID = $this->quote->getCustomer()->getGroupId();
        return (int) $this->customerGroupRepository
            ->getById($customerGroupID)
            ->getTaxClassId();
    }
}
