<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Block\Quote;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Block\Quote\Totals;
use Magento\NegotiableQuote\Helper\Quote;
use Magento\NegotiableQuote\Model\Quote\TotalsFactory;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CurrencyInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TotalsTest extends TestCase
{
    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var PostHelper|MockObject
     */
    private $postDataHelper;

    /**
     * @var Quote|MockObject
     */
    private $negotiableQuoteHelper;

    /**
     * @var Config|MockObject
     */
    private $taxConfig;

    /**
     * @var RestrictionInterface|MockObject
     */
    private $restriction;

    /**
     * @var TotalsFactory|MockObject
     */
    private $quoteTotalsFactory;

    /**
     * @var CartInterface|MockObject
     */
    private $quote;

    /**
     * @var NegotiableQuoteInterface|MockObject
     */
    private $negotiableQuote;

    /**
     * @var Totals|MockObject
     */
    private $totals;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->quoteRepository = $this->getMockForAbstractClass(
            CartRepositoryInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['get']
        );
        $this->postDataHelper = $this->createMock(PostHelper::class);
        $this->negotiableQuoteHelper = $this->createMock(Quote::class);
        $this->taxConfig =
            $this->createPartialMock(Config::class, ['displaySalesTaxWithGrandTotal']);
        $this->restriction = $this->getMockForAbstractClass(RestrictionInterface::class);
        $this->quoteTotalsFactory =
            $this->createPartialMock(TotalsFactory::class, ['create']);
        $this->quote = $this->getMockForAbstractClass(
            CartInterface::class,
            [],
            '',
            false,
            true,
            true,
            [
                'getCatalogTotalPriceWithoutTax',
                'getCatalogTotalPriceWithTax',
                'getCartTotalDiscount',
                'getOriginalTaxValue',
                'getCatalogTotalPrice',
                'getSubtotal',
                'getGrandTotal',
                'collectTotals',
                'getExtensionAttributes',
                'getShippingAddress',
                'getCurrency',
                'getTaxValue'
            ]
        );
        $this->negotiableQuote = $this->getMockForAbstractClass(
            NegotiableQuoteInterface::class,
            ['getShippingPrice'],
            '',
            false,
            true,
            true,
            []
        );
        $this->storeManager = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->scopeConfig = $this->getMockForAbstractClass(ScopeConfigInterface::class);
    }

    /**
     * Tests getTotals() method.
     *
     * @return void
     */
    public function testGetTotals()
    {
        $this->storeManager->expects($this->any())->method('getStore');
        $this->scopeConfig->expects($this->once())->method('getValue');
        $this->quoteTotalsFactory->expects($this->once())->method('create')->willReturn($this->quote);

        $quoteCurrencyCode = 'USD';
        $baseToQuoteRate = 1.4;
        $quoteCurrency = $this->getMockBuilder(CurrencyInterface::class)
            ->setMethods([
                'getBaseToQuoteRate',
                'getQuoteCurrencyCode'
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quoteCurrency->expects($this->any())->method('getBaseToQuoteRate')->willReturn($baseToQuoteRate);
        $quoteCurrency->expects($this->any())->method('getQuoteCurrencyCode')->willReturn($quoteCurrencyCode);

        $this->quote->expects($this->exactly(4))->method('getCurrency')->willReturn($quoteCurrency);
        $this->quote->expects($this->once())->method('getCatalogTotalPriceWithoutTax');
        $this->quote->expects($this->once())->method('getCatalogTotalPriceWithTax');
        $this->quote->expects($this->exactly(2))->method('getCartTotalDiscount');
        $this->quote->expects($this->once())->method('getOriginalTaxValue');
        $this->quote->expects($this->once())->method('getCatalogTotalPrice');
        $this->quote->expects($this->once())->method('getTaxValue');

        $this->taxConfig->expects($this->any())->method('displaySalesTaxWithGrandTotal');
        $this->negotiableQuoteHelper->expects($this->any())->method('resolveCurrentQuote')->willReturn($this->quote);
        $cartExtension = $this->getMockForAbstractClass(
            CartExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getParam', 'getNegotiableQuote']
        );
        $this->quote->expects($this->any())->method('getExtensionAttributes')->willReturn($cartExtension);
        $cartExtension->expects($this->any())->method('getNegotiableQuote')->willReturn($this->negotiableQuote);
        $this->negotiableQuote->expects($this->once())->method('getShippingPrice');
        $this->quote->expects($this->exactly(2))->method('getGrandTotal');
        $this->createSUT();
        $this->totals->getTotals();
    }

    /**
     * Create totals object.
     *
     * @return void
     */
    protected function createSUT()
    {
        $objectManager = new ObjectManager($this);
        $this->totals = $objectManager->getObject(
            Totals::class,
            [
                'quoteRepository' => $this->quoteRepository,
                'postDataHelper' => $this->postDataHelper,
                'negotiableQuoteHelper' => $this->negotiableQuoteHelper,
                'restriction' => $this->restriction,
                'taxConfig' => $this->taxConfig,
                'quoteTotalsFactory' => $this->quoteTotalsFactory,
                '_storeManager' => $this->storeManager,
                '_scopeConfig' => $this->scopeConfig,
                'data' => [],
            ]
        );
    }
}
