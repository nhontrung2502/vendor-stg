<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model;

use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\CartFactory;
use Magento\Framework\App\Area;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Helper\Quote;
use Magento\NegotiableQuote\Model\Cart;
use Magento\NegotiableQuote\Model\NegotiableQuote;
use Magento\NegotiableQuote\Model\Quote\Totals;
use Magento\NegotiableQuote\Model\Quote\TotalsFactory;
use Magento\NegotiableQuote\Model\QuoteItemsUpdater;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class QuoteItemsUpdaterTest extends TestCase
{
    /**
     * @var Quote|MockObject
     */
    private $negotiableQuoteHelper;

    /**
     * @var Cart|MockObject
     */
    private $cartMock;

    /**
     * @var QuoteItemsUpdater
     */
    private $quoteItemsUpdater;

    /**
     * @var \Magento\Quote\Model\Quote|MockObject
     */
    private $quote;

    /**
     * @var TotalsFactory|MockObject
     */
    private $quoteTotalsFactory;

    /**
     * @var CartFactory|MockObject
     */
    private $cartFactory;

    /**
     * @var NegotiableQuoteInterface|MockObject
     */
    private $negotiableQuote;

    /**
     * @var Json|MockObject
     */
    private $serializerMock;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->quote = $this->getQuote();
        $this->cartMock = $this->getMockBuilder(Cart::class)
            ->onlyMethods(['addItems', 'getDeletedItemsSku', 'addConfigureditems'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->negotiableQuoteHelper = $this->getNegotiableQuoteHelper();
        $this->quoteTotalsFactory =
            $this->createPartialMock(TotalsFactory::class, ['create']);
        $this->cartFactory = $this->createPartialMock(CartFactory::class, ['create']);

        $this->serializerMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['serialize', 'unserialize'])
            ->getMock();

        $this->serializerMock->expects($this->any())
            ->method('serialize')
            ->willReturnCallback(
                function ($value) {
                    return json_encode($value);
                }
            );

        $this->serializerMock->expects($this->any())
            ->method('unserialize')
            ->willReturnCallback(
                function ($value) {
                    return json_decode($value, true);
                }
            );

        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();
        $this->negotiableQuote = $this->getMockBuilder(NegotiableQuote::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getNegotiatedPriceValue',
                    'setIsCustomerPriceChanged',
                    'getDeletedSku',
                    'setDeletedSku'
                ]
            )
            ->getMock();
        $extensionAttributes->expects($this->any())->method('getNegotiableQuote')->willReturn($this->negotiableQuote);
        $this->quote->expects($this->any())->method('getExtensionAttributes')->willReturn($extensionAttributes);

        $objectManager = new ObjectManager($this);
        $this->quoteItemsUpdater = $objectManager->getObject(
            QuoteItemsUpdater::class,
            [
                'negotiableQuoteHelper' => $this->negotiableQuoteHelper,
                'quoteTotalsFactory' => $this->quoteTotalsFactory,
                'cartFactory' => $this->cartFactory,
                'cart' => $this->cartMock,
                'serializer' => $this->serializerMock
            ]
        );
    }

    /**
     * Get negotiableQuoteHelper mock.
     *
     * @return Quote|MockObject
     */
    private function getNegotiableQuoteHelper()
    {
        $negotiableQuoteHelper = $this->getMockBuilder(Quote::class)
            ->addMethods(['setHasChangesInNegotiableQuote'])
            ->onlyMethods(['retrieveCustomOptions'])
            ->disableOriginalConstructor()
            ->getMock();
        $negotiableQuoteHelper->expects($this->any())
            ->method('setHasChangesInNegotiableQuote')
            ->willReturnSelf();
        $negotiableQuoteHelper->expects($this->any())
            ->method('retrieveCustomOptions')
            ->willReturn(['test']);

        return $negotiableQuoteHelper;
    }

    /**
     * Get quote mock.
     *
     * @return \Magento\Quote\Model\Quote|MockObject
     */
    private function getQuote()
    {
        $quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)->disableOriginalConstructor()
            ->setMethods(
                [
                    'removeAllItems',
                    'getItemsCollection',
                    'getAllVisibleItems',
                    'setData',
                    'getData',
                    'getItemById',
                    'removeItem',
                    'getExtensionAttributes'
                ]
            )
            ->getMock();
        $itemsCollection = [];
        $quote->expects($this->any())->method('getItemsCollection')->willReturn($itemsCollection);

        return $quote;
    }

    /**
     * Test for updateItemsForQuote() method.
     *
     * @dataProvider updateQuoteItemsTestDataProvider
     * @param array $data
     * @param string $deletedSku
     * @param array $failedSku
     * @param string $deletedSkuResult
     * @param bool $canConfigure
     * @param bool $expect
     * @return void
     */
    public function testUpdateItemsForQuote(
        array $data,
        $deletedSku,
        array $failedSku,
        $deletedSkuResult,
        $canConfigure,
        $expect
    ) {
        $this->cartMock->expects($this->any())
            ->method('addConfiguredItems')
            ->willReturn(true);
        $this->cartMock->expects($this->any())
            ->method('addItems')
            ->willReturn(true);
        $productMock = $this->createPartialMock(Product::class, ['canConfigure']);
        $productMock->expects($this->any())->method('canConfigure')->willReturn($canConfigure);
        $item = $this->getMockBuilder(Item::class)
            ->addMethods(['canConfigure'])
            ->onlyMethods(['getProduct', 'setQty'])
            ->disableOriginalConstructor()
            ->getMock();
        $item->expects($this->any())->method('getProduct')->willReturn($productMock);
        $this->quote->expects($this->any())->method('getItemById')->willReturn($item);
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([$item]);
        $this->cartMock->expects($this->once())->method('getDeletedItemsSku')->willReturn($failedSku);
        $this->negotiableQuote->expects(count($failedSku) ? $this->once() : $this->never())
            ->method('getDeletedSku')->willReturn($deletedSku);
        $this->negotiableQuote->expects(count($failedSku) ? $this->once() : $this->never())
            ->method('setDeletedSku')->with($deletedSkuResult)->willReturnSelf();

        $this->assertEquals($expect, $this->quoteItemsUpdater->updateItemsForQuote($this->quote, $data, true));
    }

    /**
     * DataProvider for testUpdateItemsForQuote.
     *
     * @return array
     */
    public function updateQuoteItemsTestDataProvider()
    {
        return [
            [
                [
                    'items' => [
                        ['sku' => 1, 'qty' => 2, 'id' => 1],
                        ['sku' => 2, 'qty' => 1, 'id' => 2]
                    ],
                    'update' => 1
                ],
                json_encode([
                    Area::AREA_ADMINHTML => [3],
                    Area::AREA_FRONTEND => []
                ]),
                [2],
                json_encode([
                    Area::AREA_ADMINHTML => [3, 2],
                    Area::AREA_FRONTEND => []
                ]),
                false,
                true
            ],
            [
                [
                    'items' => [
                        ['sku' => 1, 'qty' => 2, 'id' => 1, 'productSku' => 'test', 'config' => 'test'],
                        ['sku' => 2, 'qty' => 1, 'id' => 2, 'productSku' => 'test2', 'config' => 'test2'],
                    ],
                    'update' => 0
                ],
                '',
                [2],
                json_encode([
                    Area::AREA_ADMINHTML => [2],
                    Area::AREA_FRONTEND => []
                ]),
                true,
                true
            ],
            [
                [
                    'items' => [
                        ['sku' => 1, 'qty' => null, 'id' => null, 'productSku' => 'test', 'config' => 'test'],
                    ],
                    'update' => 0
                ],
                '',
                [],
                '',
                false,
                true
            ],
            [
                [
                    'items' => [
                        ['sku' => 1, 'qty' => null, 'id' => null, 'productSku' => 'test', 'config' => 'test'],
                    ],
                    'update' => 0
                ],
                '',
                [],
                '',
                true,
                true
            ],
            [
                [
                    'items' => [
                        ['sku' => 1, 'qty' => null, 'id' => null, 'productSku' => 'test', 'config' => null],
                    ],
                    'update' => 0
                ],
                '',
                [],
                '',
                false,
                true
            ],
            [
                [
                    'addItems' => [
                        ['sku' => 1, 'qty' => 2, 'id' => null],
                        ['sku' => 2, 'qty' => 3, 'id' => null],
                    ],
                    'update' => 1
                ],
                '',
                [],
                '',
                false,
                true
            ],
        ];
    }

    /**
     * Test for updateQuoteItemsByCartData() method.
     *
     * @return void
     */
    public function testUpdateQuoteItemsByCartData()
    {
        $totals = $this->createMock(Totals::class);
        $totals->expects($this->any())->method('getCatalogTotalPrice')->willReturnOnConsecutiveCalls(22, 20);
        $cart = $this->createMock(\Magento\Checkout\Model\Cart::class);
        $this->quoteTotalsFactory->expects($this->once())->method('create')->willReturn($totals);
        $this->cartFactory->expects($this->once())->method('create')->willReturn($cart);
        $cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->negotiableQuote->expects($this->atLeastOnce())->method('getNegotiatedPriceValue')->willReturn(17);
        $this->negotiableQuote->expects($this->once())
            ->method('setIsCustomerPriceChanged')->with(true)->willReturnSelf();

        $this->assertEquals(
            $this->quote,
            $this->quoteItemsUpdater->updateQuoteItemsByCartData($this->quote, [['qty' => 1]])
        );
    }
}
