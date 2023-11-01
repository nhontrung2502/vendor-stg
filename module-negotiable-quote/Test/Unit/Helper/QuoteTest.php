<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Helper;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Company\Api\AuthorizationInterface;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyExtensionInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\CompanyQuoteConfigInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Helper\Quote;
use Magento\NegotiableQuote\Model\PriceFormatter;
use Magento\NegotiableQuote\Model\Quote\ViewAccessInterface;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\NegotiableQuote\Helper\Quote class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class QuoteTest extends TestCase
{
    /**
     * @var StockRegistryInterface|MockObject
     */
    private $stockRepository;

    /**
     * @var RestrictionInterface|MockObject
     */
    private $restriction;

    /**
     * @var CompanyManagementInterface|MockObject
     */
    private $companyManagement;

    /**
     * @var CartInterface|MockObject
     */
    private $quote;

    /**
     * @var CartInterface|MockObject
     */
    private $snapshotQuote;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var NegotiableQuoteManagementInterface|MockObject
     */
    private $negotiableQuoteManagement;

    /**
     * @var AuthorizationInterface|MockObject
     */
    private $authorization;

    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var PriceFormatter|MockObject
     */
    private $priceFormatter;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var ViewAccessInterface|MockObject
     */
    private $viewAccess;

    /**
     * @var Quote
     */
    private $helper;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->stockRepository = $this->getMockBuilder(StockRegistryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->restriction = $this->getMockBuilder(
            RestrictionInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyManagement = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllItems'])
            ->getMockForAbstractClass();
        $this->snapshotQuote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->negotiableQuoteManagement = $this->getMockBuilder(
            NegotiableQuoteManagementInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->authorization = $this->getMockBuilder(AuthorizationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->userContext = $this->getMockBuilder(UserContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->priceFormatter = $this->getMockBuilder(PriceFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->viewAccess = $this->getMockForAbstractClass(ViewAccessInterface::class);

        $objectManagerHelper = new ObjectManager($this);
        $this->helper = $objectManagerHelper->getObject(
            Quote::class,
            [
                'stockRepository' => $this->stockRepository,
                'restriction' => $this->restriction,
                'companyManagement' => $this->companyManagement,
                'quoteRepository' => $this->quoteRepository,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagement,
                'authorization' => $this->authorization,
                'userContext' => $this->userContext,
                'priceFormatter' => $this->priceFormatter,
                '_request' => $this->request,
                'viewAccess' => $this->viewAccess,
            ]
        );
    }

    /**
     * Test resolveCurrentQuote method if quote is not a snapshot.
     *
     * @return void
     */
    public function testResolveCurrentQuoteNotSnapshot(): void
    {
        $quoteId = 1;
        $this->request->expects($this->once())->method('getParam')->with('quote_id')->willReturn($quoteId);
        $this->quoteRepository->expects($this->once())->method('get')->with($quoteId, ['*'])->willReturn($this->quote);
        $this->viewAccess->expects($this->once())->method('canViewQuote')->with($this->quote)->willReturn(true);

        $this->assertSame($this->quote, $this->helper->resolveCurrentQuote());
    }

    /**
     * Test resolveCurrentQuote method if quote doesn't exist.
     *
     * @return void
     */
    public function testResolveCurrentQuoteWithException(): void
    {
        $quoteId = 1;
        $exception = new NoSuchEntityException();
        $this->request->expects($this->once())->method('getParam')->with('quote_id')->willReturn($quoteId);
        $this->quoteRepository->expects($this->once())
            ->method('get')
            ->with($quoteId, ['*'])
            ->willThrowException($exception);

        $this->assertNull($this->helper->resolveCurrentQuote());
    }

    /**
     * Test resolveCurrentQuote method if quote is a snapshot.
     *
     * @return void
     */
    public function testResolveCurrentQuoteIsSnapshot(): void
    {
        $quoteId = 1;
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();
        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->request->expects($this->once())->method('getParam')->with('quote_id')->willReturn($quoteId);
        $this->quoteRepository->expects($this->once())->method('get')->with($quoteId, ['*'])->willReturn($this->quote);
        $this->restriction->expects($this->once())->method('setQuote')->with($this->quote)->willReturnSelf();
        $this->restriction->expects($this->once())->method('canSubmit')->willReturn(false);
        $this->quote->expects($this->once())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $extensionAttributes->expects($this->once())->method('getNegotiableQuote')->willReturn($negotiableQuote);
        $this->quote->expects($this->once())->method('getId')->willReturn($quoteId);
        $this->negotiableQuoteManagement->expects($this->once())
            ->method('getSnapshotQuote')
            ->with($quoteId)
            ->willReturn($this->snapshotQuote);
        $this->viewAccess->expects($this->once())->method('canViewQuote')->with($this->quote)->willReturn(true);

        $this->assertSame($this->snapshotQuote, $this->helper->resolveCurrentQuote(true));
    }

    /**
     * Test isEnabled method.
     *
     * @return void
     */
    public function testIsEnabled(): void
    {
        $userId = 1;
        $extensionAttributes = $this->getMockBuilder(CompanyExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuoteConfig'])
            ->getMockForAbstractClass();
        $quoteConfig = $this->getMockBuilder(CompanyQuoteConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($userId);
        $this->companyManagement->expects($this->once())
            ->method('getByCustomerId')
            ->with($userId)
            ->willReturn($company);
        $company->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $extensionAttributes->expects($this->atLeastOnce())->method('getQuoteConfig')->willReturn($quoteConfig);
        $quoteConfig->expects($this->atLeastOnce())->method('getIsQuoteEnabled')->willReturn(true);

        $this->assertTrue($this->helper->isEnabled());
    }

    /**
     * Test isEnabled method with exception.
     *
     * @return void
     */
    public function testIsEnabledWithException(): void
    {
        $userId = 1;
        $exception = new NoSuchEntityException();
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($userId);
        $this->companyManagement->expects($this->once())
            ->method('getByCustomerId')
            ->with($userId)
            ->willThrowException($exception);

        $this->assertFalse($this->helper->isEnabled());
    }

    /**
     * Test getUserId method.
     *
     * @return void
     */
    public function testGetCurrentUserId(): void
    {
        $userId = 1;
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($userId);

        $this->assertSame($userId, $this->helper->getCurrentUserId());
    }

    /**
     * Test getSalesRepresentative method.
     *
     * @param bool $returnId
     * @param int|string $expectedResult
     * @param int $count
     * @return void
     * @dataProvider getSalesRepresentativeIdDataProvider
     */
    public function testGetSalesRepresentative(bool $returnId, $expectedResult, int $count): void
    {
        $quoteId = 1;
        $customerId = 1;
        $salesRepId = 1;
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quoteRepository->expects($this->once())->method('get')->with($quoteId)->willReturn($this->quote);
        $this->quote->expects($this->atLeastOnce())->method('getCustomer')->willReturn($customer);
        $customer->expects($this->atLeastOnce())->method('getId')->willReturn($customerId);
        $this->companyManagement->expects($this->once())
            ->method('getByCustomerId')
            ->with($customerId)
            ->willReturn($company);
        $company->expects($this->once())->method('getSalesRepresentativeId')->willReturn($salesRepId);
        $this->companyManagement->expects($this->exactly($count))
            ->method('getSalesRepresentative')
            ->with($salesRepId)
            ->willReturn('Sales Rep');

        $this->assertSame($expectedResult, $this->helper->getSalesRepresentative($quoteId, $returnId));
    }

    /**
     * Data provider for getSalesRepresentative method.
     *
     * @return array
     */
    public function getSalesRepresentativeIdDataProvider(): array
    {
        return [
            [true, 1, 0],
            [false, 'Sales Rep', 1],
        ];
    }

    /**
     * Test getSalesRepresentative method with exception.
     *
     * @return void
     */
    public function testGetSalesRepresentativeWithException(): void
    {
        $quoteId = 1;
        $customerId = 1;
        $exception = new NoSuchEntityException();
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quoteRepository->expects($this->once())->method('get')->with($quoteId)->willReturn($this->quote);
        $this->quote->expects($this->atLeastOnce())->method('getCustomer')->willReturn($customer);
        $customer->expects($this->atLeastOnce())->method('getId')->willReturn($customerId);
        $this->companyManagement->expects($this->once())
            ->method('getByCustomerId')
            ->with($customerId)
            ->willThrowException($exception);

        $this->assertFalse($this->helper->getSalesRepresentative($quoteId));
    }

    /**
     * Test formatPrice method.
     *
     * @return void
     */
    public function testFormatPrice(): void
    {
        $price = 100.0000;
        $code = 'USD';
        $this->priceFormatter->expects($this->once())
            ->method('formatPrice')
            ->with($price, $code)
            ->willReturn('$100.00');

        $this->assertEquals('$100.00', $this->helper->formatPrice($price, $code));
    }

    /**
     * Test isLockMessageDisplayed method.
     *
     * @param bool $isMessageDisplayed
     * @param bool $expectedResult
     * @return void
     * @dataProvider isLockMessageDisplayedDataProvider
     */
    public function testIsLockMessageDisplayed(bool $isMessageDisplayed, bool $expectedResult): void
    {
        $this->restriction->expects($this->once())->method('isLockMessageDisplayed')->willReturn($isMessageDisplayed);

        $this->assertEquals($expectedResult, $this->helper->isLockMessageDisplayed());
    }

    /**
     * Data provider for isLockMessageDisplayed method.
     *
     * @return array
     */
    public function isLockMessageDisplayedDataProvider(): array
    {
        return [
            [true, true],
            [false, false],
        ];
    }

    /**
     * Test isExpiredMessageDisplayed method.
     *
     * @param bool $isMessageDisplayed
     * @param bool $expectedResult
     * @return void
     * @dataProvider isExpiredMessageDisplayedDataProvider
     */
    public function testIsExpiredMessageDisplayed(bool $isMessageDisplayed, bool $expectedResult): void
    {
        $this->restriction->expects($this->once())
            ->method('isExpiredMessageDisplayed')
            ->willReturn($isMessageDisplayed);

        $this->assertEquals($expectedResult, $this->helper->isExpiredMessageDisplayed());
    }

    /**
     * Data provider for isExpiredMessageDisplayed method.
     *
     * @return array
     */
    public function isExpiredMessageDisplayedDataProvider(): array
    {
        return [
            [true, true],
            [false, false],
        ];
    }

    /**
     * Test getFormattedOriginalPrice method.
     *
     * @return void
     */
    public function testGetFormattedOriginalPrice(): void
    {
        $item = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quoteCurrency = 'USD';
        $baseCurrency = 'EUR';
        $this->priceFormatter->expects($this->once())
            ->method('getFormattedOriginalPrice')
            ->with($item, $quoteCurrency, $baseCurrency)
            ->willReturn('$100.00');

        $this->assertEquals('$100.00', $this->helper->getFormattedOriginalPrice($item, $quoteCurrency, $baseCurrency));
    }

    /**
     * Test getFormattedCartPrice method.
     *
     * @return void
     */
    public function testGetFormattedCartPrice(): void
    {
        $item = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quoteCurrency = 'USD';
        $baseCurrency = 'EUR';
        $this->priceFormatter->expects($this->once())
            ->method('getFormattedCartPrice')
            ->with($item, $quoteCurrency, $baseCurrency)
            ->willReturn('$100.00');

        $this->assertEquals('$100.00', $this->helper->getFormattedCartPrice($item, $quoteCurrency, $baseCurrency));
    }

    /**
     * Test retrieveCustomOptions method.
     *
     * @return void
     */
    public function testRetrieveCustomOptions(): void
    {
        $item = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBuyRequest'])
            ->getMockForAbstractClass();
        $buyRequest = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['hasData', 'getData'])
            ->getMock();
        $options = [
            'super_attribute' => 'option_data',
            'options' => 'option_data',
            'bundle_option' => 'option_data',
            'custom_giftcard_amount' => 'option_data',
            'giftcard_amount' => 'option_data',
            'giftcard_message' => 'option_data',
            'giftcard_recipient_email' => 'option_data',
            'giftcard_recipient_name' => 'option_data',
            'giftcard_sender_email' => 'option_data',
            'giftcard_sender_name' => 'option_data',
        ];
        $item->expects($this->once())->method('getBuyRequest')->willReturn($buyRequest);
        $buyRequest->expects($this->atLeastOnce())->method('hasData')->willReturn(true);
        $buyRequest->expects($this->atLeastOnce())->method('getData')->willReturn('option_data');
        $result = http_build_query($options);

        $this->assertEquals($result, $this->helper->retrieveCustomOptions($item, true));
    }

    /**
     * Test getFormattedCatalogPrice method.
     *
     * @return void
     */
    public function testGetFormattedCatalogPrice(): void
    {
        $item = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quoteCurrency = 'USD';
        $baseCurrency = 'EUR';
        $this->priceFormatter->expects($this->once())
            ->method('getFormattedCatalogPrice')
            ->with($item, $quoteCurrency, $baseCurrency)
            ->willReturn('$100.00');

        $this->assertEquals('$100.00', $this->helper->getFormattedCatalogPrice($item, $quoteCurrency, $baseCurrency));
    }

    /**
     * Test isSubmitAvailable method.
     *
     * @param bool $canSubmit
     * @param bool $expectedResult
     * @return void
     * @dataProvider isSubmitAvailableDataProvider
     */
    public function testIsSubmitAvailable(bool $canSubmit, bool $expectedResult): void
    {
        $this->restriction->expects($this->once())
            ->method('canSubmit')
            ->willReturn($canSubmit);

        $this->assertEquals($expectedResult, $this->helper->isSubmitAvailable());
    }

    /**
     * Data provider for isSubmitAvailable method.
     *
     * @return array
     */
    public function isSubmitAvailableDataProvider(): array
    {
        return [
            [true, true],
            [false, false],
        ];
    }

    /**
     * Test getStockForProduct method.
     *
     * @return void
     */
    public function testGetStockForProduct(): void
    {
        $productId = 1;
        $qty = 1;
        $item = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuote', 'getId'])
            ->getMockForAbstractClass();
        $itemQuote = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParentItemId', 'getProduct'])
            ->getMockForAbstractClass();
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $stockItem = $this->getMockBuilder(StockItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item->expects($this->once())->method('getProductType')->willReturn('configurable');
        $item->expects($this->once())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->once())->method('getAllItems')->willReturn([$itemQuote]);
        $itemQuote->expects($this->once())->method('getParentItemId')->willReturn(2);
        $item->expects($this->once())->method('getId')->willReturn(2);
        $itemQuote->expects($this->once())->method('getProduct')->willReturn($product);
        $product->expects($this->once())->method('getId')->willReturn($productId);
        $this->stockRepository->expects($this->once())
            ->method('getStockItem')
            ->with($productId)
            ->willReturn($stockItem);
        $stockItem->expects($this->once())->method('getQty')->willReturn($qty);

        $this->assertEquals(1, $this->helper->getStockForProduct($item));
    }

    /**
     * Test isAllowedManage method.
     *
     * @param bool $isAllowed
     * @param bool $expectedResult
     * @return void
     * @dataProvider isAllowedManageDataProvider
     */
    public function testIsAllowedManage(bool $isAllowed, bool $expectedResult): void
    {
        $this->authorization->expects($this->once())
            ->method('isAllowed')
            ->with('Magento_NegotiableQuote::manage')
            ->willReturn($isAllowed);

        $this->assertEquals($expectedResult, $this->helper->isAllowedManage());
    }

    /**
     * Data provider for isAllowedManage method.
     *
     * @return array
     */
    public function isAllowedManageDataProvider(): array
    {
        return [
            [true, true],
            [false, false],
        ];
    }

    /**
     * Test getItemTotal method.
     *
     * @return void
     */
    public function testGetItemTotal(): void
    {
        $item = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quoteCurrency = 'USD';
        $baseCurrency = 'EUR';
        $this->priceFormatter->expects($this->once())
            ->method('getItemTotal')
            ->with($item, $quoteCurrency, $baseCurrency)
            ->willReturn('$100.00');

        $this->assertEquals('$100.00', $this->helper->getItemTotal($item, $quoteCurrency, $baseCurrency));
    }
}
