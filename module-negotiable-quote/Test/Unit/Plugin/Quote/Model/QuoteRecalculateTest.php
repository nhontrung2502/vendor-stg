<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Plugin\Quote\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface;
use Magento\NegotiableQuote\Model\Quote\ItemRemove;
use Magento\NegotiableQuote\Plugin\Quote\Model\QuoteRecalculate;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtension;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\ResourceModel\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class QuoteRecalculateTest extends TestCase
{
    /**
     * @var QuoteRecalculate
     */
    private $model;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $cartRepositoryMock;

    /**
     * @var Item|MockObject
     */
    private $itemResourceMock;

    /**
     * @var ItemRemove|MockObject
     */
    private $itemRemoveMock;

    /**
     * @var NegotiableQuoteItemManagementInterface|MockObject
     */
    private $itemManagementMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    protected function setUp(): void
    {
        $this->cartRepositoryMock = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->itemResourceMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->itemRemoveMock = $this->getMockBuilder(ItemRemove::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->itemManagementMock =
            $this->getMockBuilder(NegotiableQuoteItemManagementInterface::class)
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->model = new QuoteRecalculate(
            $this->cartRepositoryMock,
            $this->itemResourceMock,
            $this->itemRemoveMock,
            $this->itemManagementMock,
            $this->loggerMock
        );
    }

    public function testUpdateQuotesByProductIfQuoteDoesNotExist()
    {
        $tableName = 'table_name';
        $productId = 100;
        $itemsArray = [100 => 'sku'];
        $result = 'result';
        $closure = function () use ($result) {
            return $result;
        };
        $productMock = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $adapterMock = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productMock->expects($this->once())->method('getId')->willReturn($productId);
        $this->itemResourceMock->expects($this->once())->method('getConnection')->willReturn($adapterMock);
        $this->itemResourceMock->expects($this->once())->method('getMainTable')->willReturn($tableName);

        $adapterMock->expects($this->once())->method('select')->willReturn($selectMock);
        $selectMock->expects($this->once())->method('reset')->willReturnSelf();
        $selectMock->expects($this->once())->method('from')->with($tableName, ['sku', 'quote_id'])->willReturnSelf();
        $selectMock->expects($this->once())->method('where')->with('product_id = ?', $productId)->willReturnSelf();

        $adapterMock->expects($this->once())->method('fetchPairs')->with($selectMock)->willReturn($itemsArray);

        $exception = new \Exception('Quote not found');
        $this->cartRepositoryMock->expects($this->once())
            ->method('get')
            ->with(100, ['*'])
            ->willThrowException($exception);

        $this->loggerMock->expects($this->once())->method('critical')->with($exception);
        $this->model->updateQuotesByProduct($closure, $productMock);
    }

    public function testUpdateQuotesByProductIfQuoteNotNegotiable()
    {
        $tableName = 'table_name';
        $productId = 20;
        $itemsArray = [10 => 'sku'];
        $result = 'result';
        $closure = function () use ($result) {
            return $result;
        };
        $productMock = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $productMock->expects($this->once())->method('getId')->willReturn($productId);
        $adapterMock = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->itemResourceMock->expects($this->once())->method('getConnection')->willReturn($adapterMock);
        $this->itemResourceMock->expects($this->once())->method('getMainTable')->willReturn($tableName);

        $adapterMock->expects($this->once())->method('select')->willReturn($selectMock);
        $adapterMock->expects($this->once())->method('fetchPairs')->with($selectMock)->willReturn($itemsArray);

        $selectMock->expects($this->once())->method('reset')->willReturnSelf();
        $selectMock->expects($this->once())->method('from')->with($tableName, ['sku', 'quote_id'])->willReturnSelf();
        $selectMock->expects($this->once())->method('where')->with('product_id = ?', $productId)->willReturnSelf();

        $quoteMock = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->cartRepositoryMock->expects($this->once())
            ->method('get')
            ->with(10, ['*'])
            ->willReturn($quoteMock);

        $extensionAttrsMock = $this->getMockBuilder(CartExtension::class)
            ->setMethods(['getNegotiableQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock->expects($this->exactly(2))->method('getExtensionAttributes')->willReturn($extensionAttrsMock);
        $extensionAttrsMock->expects($this->once())->method('getNegotiableQuote')->willReturn(null);

        $this->itemRemoveMock->expects($this->never())->method('setNotificationRemove');
        $this->itemManagementMock->expects($this->never())->method('updateQuoteItemsCustomPrices');

        $this->assertEquals($result, $this->model->updateQuotesByProduct($closure, $productMock));
    }

    public function testUpdateQuotesByProduct()
    {
        $quoteId = 20;
        $tableName = 'table_name';
        $productId = 200;
        $itemsArray = [$quoteId => 'sku'];
        $result = 'result';
        $closure = function () use ($result) {
            return $result;
        };
        $productMock = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $productMock->expects($this->exactly(2))->method('getId')->willReturn($productId);

        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $selectMock->expects($this->once())->method('reset')->willReturnSelf();
        $selectMock->expects($this->once())->method('from')->with($tableName, ['sku', 'quote_id'])->willReturnSelf();
        $selectMock->expects($this->once())->method('where')->with('product_id = ?', $productId)->willReturnSelf();

        $adapterMock = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $adapterMock->expects($this->once())->method('select')->willReturn($selectMock);
        $adapterMock->expects($this->once())->method('fetchPairs')->with($selectMock)->willReturn($itemsArray);

        $this->itemResourceMock->expects($this->once())->method('getConnection')->willReturn($adapterMock);
        $this->itemResourceMock->expects($this->once())->method('getMainTable')->willReturn($tableName);

        $quoteMock = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->cartRepositoryMock->expects($this->once())
            ->method('get')
            ->with($quoteId, ['*'])
            ->willReturn($quoteMock);

        $extensionAttrsMock = $this->getMockBuilder(CartExtension::class)
            ->setMethods(['getNegotiableQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock->expects($this->exactly(3))->method('getExtensionAttributes')->willReturn($extensionAttrsMock);

        $negotiableQuoteMock = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $extensionAttrsMock->expects($this->exactly(2))->method('getNegotiableQuote')->willReturn($negotiableQuoteMock);
        $negotiableQuoteMock->expects($this->once())->method('getIsRegularQuote')->willReturn(true);

        $this->itemRemoveMock->expects($this->once())
            ->method('setNotificationRemove')
            ->with($quoteId, $productId, [$itemsArray[$quoteId]]);
        $this->itemManagementMock->expects($this->once())
            ->method('updateQuoteItemsCustomPrices')
            ->with($quoteId);

        $this->assertEquals($result, $this->model->updateQuotesByProduct($closure, $productMock));
    }
}
