<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Plugin\AdvancedCheckout\Model;

use Magento\AdvancedCheckout\Model\Cart;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Plugin\AdvancedCheckout\Model\CartPlugin;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for \Magento\NegotiableQuote\Plugin\AdvancedCheckout\Model\CartPlugin.
 */
class CartPluginTest extends TestCase
{
    /**
     * @var CartPlugin|null
     */
    private $quoteCurrentStorePlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->quoteCurrentStorePlugin = $objectManager->getObject(
            CartPlugin::class,
            []
        );
    }

    /**
     * Test for afterGetCurrentStore().
     *
     * @dataProvider afterGetCurrentStoreDataProvider
     *
     * @param string $quoteStoreId
     * @param string $currentStoreId
     * @return void
     */
    public function testAfterGetCurrentStore(
        string $quoteStoreId,
        string $currentStoreId
    ): void {
        /**
         * @var Cart|MockObject $cart
         */
        $cart = $this->createMock(Cart::class);

        /**
         * @var Quote|MockObject $quote
         */
        $quote = $this->createMock(Quote::class);

        /**
         * @var Store|MockObject $store
         */
        $store = $this->createMock(Store::class);
        $currentStoreMock = $this->createMock(Store::class);

        $cart->expects($this->any())->method('getQuote')->willReturn($quote);
        $quote->expects($this->any())->method('getStore')->willReturn($store);
        $quote->expects($this->any())
            ->method('getStoreId')
            ->willReturnOnConsecutiveCalls($quoteStoreId, $currentStoreId);
        $this->assertEquals(
            $currentStoreMock,
            $this->quoteCurrentStorePlugin->afterGetCurrentStore($cart, $currentStoreMock)
        );
    }

    /**
     * Data provider for testAfterGetCurrentStore().
     *
     * @return array
     */
    public function afterGetCurrentStoreDataProvider()
    {
        return [
            'get current score when quoteStore and currentStore are same' =>
                [
                    'quoteStoreId'           => '1',
                    'currentStoreId'          => '1'
                ],
            'get current score when quoteStore and currentStore are different' =>
                [
                    'quoteStoreId'           => '1',
                    'currentStoreId'          => '3'
                ],
            'get current score when quoteStore is empty' =>
                [
                    'quoteStoreId'           => '',
                    'currentStoreId'          => '1'
                ],
        ];
    }
}
