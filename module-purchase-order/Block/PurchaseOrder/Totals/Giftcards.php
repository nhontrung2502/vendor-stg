<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Block\PurchaseOrder\Totals;

use Magento\PurchaseOrder\Block\PurchaseOrder\AbstractPurchaseOrder;
use Magento\GiftCardAccount\Model\Giftcardaccount;
use Magento\Framework\View\Element\Template\Context;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Directory\Model\Currency;
use Magento\GiftCardAccount\Helper\Data as GiftCardAccountData;

/**
 * Block class for the gift card total for a purchase order.
 *
 * @api
 * @since 100.2.0
 */
class Giftcards extends AbstractPurchaseOrder
{
    /**
     * @var GiftCardAccountData
     */
    private $giftCardAccountData;

    /**
     * @var CurrencyFactory
     */
    private $currencyFactory;

    /**
     * Giftcards constructor.
     * @param Context $context
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param GiftCardAccountData $giftCardAccountData
     * @param CurrencyFactory $currencyFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        CartRepositoryInterface $quoteRepository,
        GiftCardAccountData $giftCardAccountData,
        CurrencyFactory $currencyFactory,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $purchaseOrderRepository,
            $quoteRepository,
            $data
        );
        $this->giftCardAccountData = $giftCardAccountData;
        $this->currencyFactory = $currencyFactory;
    }

    /**
     * Retrieve gift cards applied to current purchase order
     *
     * @return array
     */
    public function getGiftCards()
    {
        $result = [];
        if ($this->getQuote()->isVirtual()) {
            $source = $this->getQuote()->getBillingAddress();
        } else {
            $source = $this->getQuote()->getShippingAddress();
        }
        $cards = $this->giftCardAccountData->getCards($source);
        foreach ($cards as $card) {
            $obj = new \Magento\Framework\DataObject();
            $obj->setBaseAmount($card[Giftcardaccount::BASE_AMOUNT])
                ->setAmount($card[Giftcardaccount::AMOUNT])
                ->setCode($card[Giftcardaccount::CODE]);

            $result[] = $obj;
        }
        return $result;
    }

    /**
     * Get formatted price value including purchases order quote currency rate
     *
     * @param float $price
     * @param bool $addBrackets
     * @return string
     */
    public function formatPrice($price, $addBrackets = false)
    {
        return $this->formatPricePrecision($price, 2, $addBrackets);
    }

    /**
     * Format price precision
     *
     * @param float $price
     * @param int $precision
     * @param bool $addBrackets
     * @return string
     */
    private function formatPricePrecision($price, $precision, $addBrackets = false)
    {
        return $this->getQuoteCurrency()->formatPrecision($price, $precision, [], true, $addBrackets);
    }

    /**
     * Get currency model instance. Will be used currency with which purchase order quote
     *
     * @return Currency
     */
    private function getQuoteCurrency()
    {
        $orderCurrency = $this->currencyFactory->create();
        $orderCurrency->load(
            $this->getQuote()->getQuoteCurrencyCode()
        );
        return $orderCurrency;
    }

    /**
     * Get total label properties
     *
     * @return string
     */
    public function getLabelProperties()
    {
        return $this->getParentBlock()->getLabelProperties();
    }

    /**
     * Get total value properties
     *
     * @return string
     */
    public function getValueProperties()
    {
        return $this->getParentBlock()->getValueProperties();
    }
}
