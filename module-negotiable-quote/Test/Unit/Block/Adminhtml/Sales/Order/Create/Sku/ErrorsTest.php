<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Block\Adminhtml\Sales\Order\Create\Sku;

use Magento\AdvancedCheckout\Helper\Data;
use Magento\AdvancedCheckout\Model\Cart;
use Magento\AdvancedCheckout\Model\CartFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\LayoutInterface;
use Magento\NegotiableQuote\Block\Adminhtml\Sales\Order\Create\Sku\Errors;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ErrorsTest extends TestCase
{
    /**
     * @var Errors
     */
    private $block;

    /**
     * @var StoreInterface|MockObject
     */
    private $store;

    /**
     * @var Cart|MockObject
     */
    private $cart;

    /**
     * @var CartFactory|MockObject
     */
    private $cartFactory;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var SessionManagerInterface|MockObject
     */
    private $session;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->session = $this->getMockForAbstractClass(
            SessionManagerInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getStoreId']
        );
        $this->cart = $this->createPartialMock(
            Cart::class,
            ['setSession', 'getSession', 'getAffectedItems']
        );
        $this->cart->expects($this->once())
            ->method('setSession')
            ->with($this->session)
            ->willReturnSelf();
        $this->cart->expects($this->any())
            ->method('getSession')
            ->willReturn($this->session);
        $this->cartFactory =
            $this->createPartialMock(CartFactory::class, ['create']);
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->storeManager = $this->getMockForAbstractClass(
            StoreManagerInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getStore']
        );
        $this->store = $this->getMockForAbstractClass(
            StoreInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );
        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($this->store);
    }

    /**
     * Create test object instance
     *
     * @return void
     */
    private function createInstance()
    {
        $objectManager = new ObjectManager($this);
        $this->block = $objectManager->getObject(
            Errors::class,
            [
                'cartFactory' => $this->cartFactory,
                '_storeManager' => $this->storeManager,
                '_backendSession' => $this->session
            ]
        );

        $layout = $this->getMockForAbstractClass(LayoutInterface::class);
        $this->block->setLayout($layout);
    }

    /**
     * Test for getStore() method
     *
     * @return void
     */
    public function testGetStore()
    {
        $this->createInstance();
        $this->assertSame($this->store, $this->block->getStore());
    }

    /**
     * Test for getCart() method
     *
     * @return void
     */
    public function testGetCart()
    {
        $this->createInstance();
        $this->assertSame($this->cart, $this->block->getCart());
    }

    /**
     * Test for getFailedItems() method
     *
     * @return void
     */
    public function testGetFailedItems()
    {
        $successfulItem = [
            'code' => Data::ADD_ITEM_STATUS_SUCCESS,
            'item' => ['item_data_2'],
        ];
        $this->createInstance();
        $this->cart->expects($this->once())->method('getAffectedItems')->willReturn(
            [
                $this->getFailedItem(),
                $successfulItem,
            ]
        );
        $this->assertEquals([$this->getFailedItem()], $this->block->getFailedItems());
    }

    /**
     * Test for getNumberOfFailed() method
     *
     * @return void
     */
    public function testGetNumberOfFailed()
    {
        $this->createInstance();
        $this->cart->expects($this->once())->method('getAffectedItems')->willReturn([$this->getFailedItem()]);
        $this->assertEquals(1, $this->block->getNumberOfFailed());
    }

    /**
     * Test for toHtml() method
     *
     * @return void
     */
    public function testToHtml()
    {
        $this->createInstance();
        $this->cart->expects($this->once())->method('getAffectedItems')->willReturn([$this->getFailedItem()]);
        $this->assertEquals('', $this->block->toHtml());
    }

    /**
     * Get failed item mock
     *
     * @return array
     */
    private function getFailedItem()
    {
        return [
            'code' => Data::ADD_ITEM_STATUS_FAILED_SKU,
            'item' => ['item_data_1'],
        ];
    }

    /**
     * Test for toHtml() method without failed items
     *
     * @return void
     */
    public function testToHtmlWithoutFailedItems()
    {
        $this->createInstance();
        $this->cart->expects($this->once())->method('getAffectedItems')->willReturn([]);
        $this->assertEquals('', $this->block->toHtml());
    }
}
