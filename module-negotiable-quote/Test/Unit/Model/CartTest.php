<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model;

use Magento\AdvancedCheckout\Helper\Data;
use Magento\AdvancedCheckout\Model\Cart;
use Magento\AdvancedCheckout\Model\CartFactory;
use Magento\Backend\Model\Session;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Magento\NegotiableQuote\Model\Cart class.
 */
class CartTest extends TestCase
{
    /**
     * @var Quote|MockObject
     */
    protected $quote;

    /**
     * @var Cart|MockObject
     */
    protected $cartMock;

    /**
     * @var Session|MockObject
     */
    private $sessionQuote;

    /**
     * @var CartFactory|MockObject
     */
    private $cartFactory;

    /**
     * @var \Magento\NegotiableQuote\Model\Cart
     */
    private $cart;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->cartFactory = $this->getMockBuilder(CartFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->sessionQuote = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartMock = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'setQuote',
                    'prepareAddProductsBySku',
                    'prepareAddProductBySku',
                    'saveAffectedProducts',
                    'setSession',
                    'removeAllAffectedItems',
                    'removeAffectedItem',
                    'getFailedItems',
                    'setContext'
                ]
            )
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->cart = $objectManager->getObject(
            \Magento\NegotiableQuote\Model\Cart::class,
            [
                'cartFactory' => $this->cartFactory,
                'sessionQuote' => $this->sessionQuote
            ]
        );
    }

    /**
     * Test for removeFailedSku method.
     *
     * @return void
     */
    public function testRemoveFailedSku()
    {
        $this->cartFactory->expects($this->once())->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->once())->method('setSession')->willReturnSelf();
        $this->cartMock->expects($this->once())->method('removeAffectedItem')
            ->with('test')
            ->willReturn(true);

        $this->cart->removeFailedSku('test');
    }

    /**
     * Test for removeAllFailed method.
     *
     * @return void
     */
    public function testRemoveAllFailed()
    {
        $this->cartFactory->expects($this->once())->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->once())->method('setSession')->willReturnSelf();
        $this->cartMock->expects($this->once())->method('removeAllAffectedItems')->willReturn(true);

        $this->cart->removeAllFailed();
    }

    /**
     * Test for addItems method.
     *
     * @return void
     */
    public function testAddItems()
    {
        $addItems = [];
        $addItems[] = [
            'sku' => 'test'
        ];
        $failedItems[] = [
            'item' => [
                'sku' => 'dummy'
            ]
        ];
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartFactory->expects($this->once())->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->once())->method('setQuote')->with($quote)->willReturnSelf();
        $this->cartMock->expects($this->once())
            ->method('setContext')
            ->with(Cart::CONTEXT_ADMIN_CHECKOUT)
            ->willReturnSelf();
        $this->cartMock->expects($this->once())->method('getFailedItems')->willReturn($failedItems);
        $this->cartMock->expects($this->once())->method('prepareAddProductsBySku')->with($addItems)->willReturnSelf();
        $this->cartMock->expects($this->once())
            ->method('saveAffectedProducts')
            ->with($this->cartMock, false)
            ->willReturnSelf();

        $this->assertTrue($this->cart->addItems($quote, $addItems));
    }

    /**
     * Test for addItems method when all items failed to be added.
     *
     * @return void
     */
    public function testAddItemsFailed()
    {
        $addItems = [];
        $addItems[] = [
            'sku' => 'dummy'
        ];
        $failedItems[] = [
            'item' => [
                'sku' => 'dummy'
            ]
        ];
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartFactory->expects($this->once())->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->once())->method('setQuote')->with($quote)->willReturnSelf();
        $this->cartMock->expects($this->once())
            ->method('setContext')
            ->with(Cart::CONTEXT_ADMIN_CHECKOUT)
            ->willReturnSelf();
        $this->cartMock->expects($this->once())->method('getFailedItems')->willReturn($failedItems);
        $this->cartMock->expects($this->once())->method('prepareAddProductsBySku')->with($addItems)->willReturnSelf();
        $this->cartMock->expects($this->once())
            ->method('saveAffectedProducts')
            ->with($this->cartMock, false)
            ->willReturnSelf();

        $this->assertFalse($this->cart->addItems($quote, $addItems));
    }

    /**
     * Test for addConfiguredItems method.
     *
     * @return void
     */
    public function testAddConfiguredItems()
    {
        $configuredItems = [
            1 => [
                'productSku' => 'testSku',
                'qty' => 1,
                'config' => 'config'
            ]
        ];
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->cartFactory->expects($this->once())->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->once())->method('setQuote')->with($quote)->willReturnSelf();
        $this->cartMock->expects($this->once())
            ->method('setContext')
            ->with(Cart::CONTEXT_ADMIN_CHECKOUT)
            ->willReturnSelf();
        $this->cartMock->expects($this->once())->method('removeAffectedItem')->with('testSku')->willReturn(true);
        $this->cartMock->expects($this->once())
            ->method('prepareAddProductBySku')
            ->with('testSku', 1, 'config')
            ->willReturn([]);
        $this->cartMock->expects($this->atLeastOnce())
            ->method('saveAffectedProducts')
            ->with($this->cartMock, false)
            ->willReturnSelf();

        $this->assertTrue($this->cart->addConfiguredItems($quote, $configuredItems));
    }

    /**
     * Test for addConfiguredItems method.
     *
     * @return void
     */
    public function testAddConfiguredItemsEmpty()
    {
        $configuredItems = [];
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->cartFactory->expects($this->once())->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->once())->method('setQuote')->with($quote)->willReturnSelf();
        $this->cartMock->expects($this->once())
            ->method('setContext')
            ->with(Cart::CONTEXT_ADMIN_CHECKOUT)
            ->willReturnSelf();

        $this->assertFalse($this->cart->addConfiguredItems($quote, $configuredItems));
    }

    /**
     * Test for getDeletedItemsSku method.
     *
     * @return void
     */
    public function testGetDeletedItemsSku()
    {
        $this->cartFactory->expects($this->once())->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->once())->method('setSession')->with($this->sessionQuote)->willReturnSelf();
        $failedItems[] = [
            'item' => [
                'sku' => 'dummy'
            ],
            'code' => Data::ADD_ITEM_STATUS_FAILED_SKU
        ];
        $this->cartMock->expects($this->once())->method('getFailedItems')->willReturn($failedItems);

        $this->assertEquals(['dummy'], $this->cart->getDeletedItemsSku());
    }
}
