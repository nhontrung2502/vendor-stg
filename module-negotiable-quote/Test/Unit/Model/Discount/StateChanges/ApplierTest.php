<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Discount\StateChanges;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Model\Discount\StateChanges\Applier;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ApplierTest extends TestCase
{
    /**
     * @var State|MockObject
     */
    protected $appState;

    /**
     * @var Applier|MockObject
     */
    protected $applier;

    /**
     * @var CartInterface|MockObject
     */
    private $quote;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->appState = $this->createMock(State::class);
        $this->quote = $this->createMock(
            CartInterface::class
        );
        $objectManager = new ObjectManager($this);
        $this->applier = $objectManager->getObject(
            Applier::class,
            [
                'appState' => $this->appState
            ]
        );
    }

    /**
     * Test setItemsHasChanges
     *
     * @param float|null $negotiatedPriceValue
     * @dataProvider dataProviderSetHasItemChanges
     */
    public function testSetHasItemChanges($negotiatedPriceValue)
    {
        $negotiableQuote = $this->createMock(
            NegotiableQuoteInterface::class
        );
        $negotiableQuote->expects($this->any())->method('getNotifications')->willReturn(0);
        $negotiableQuote->expects($this->any())->method('setNotifications')->willReturnSelf();
        $negotiableQuote->expects($this->any())->method('getNegotiatedPriceValue')->willReturn($negotiatedPriceValue);
        $quoteExtension = $this->getMockForAbstractClass(
            CartExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getNegotiableQuote']
        );
        $quoteExtension->expects($this->any())->method('getNegotiableQuote')->willReturn($negotiableQuote);
        $this->quote->expects($this->any())->method('getExtensionAttributes')->willReturn($quoteExtension);
        $this->appState->expects($this->any())->method('getAreaCode')
            ->willReturn(Area::AREA_ADMINHTML);

        $this->assertInstanceOf(
            Applier::class,
            $this->applier->setHasItemChanges($this->quote)
        );
    }

    /**
     * Test setIsDiscountChanged
     */
    public function testSetIsDiscountChanged()
    {
        $this->assertInstanceOf(
            Applier::class,
            $this->applier->setIsDiscountChanged($this->quote)
        );
    }

    /**
     * Test setIsDiscountRemovedLimit
     */
    public function testSetIsDiscountRemovedLimit()
    {
        $this->assertInstanceOf(
            Applier::class,
            $this->applier->setIsDiscountRemovedLimit($this->quote)
        );
    }

    /**
     * Test setIsDiscountRemoved
     */
    public function testSetIsDiscountRemoved()
    {
        $this->assertInstanceOf(
            Applier::class,
            $this->applier->setIsDiscountRemoved($this->quote)
        );
    }

    /**
     * Test setIsTaxChanged
     */
    public function testSetIsTaxChanged()
    {
        $this->assertInstanceOf(
            Applier::class,
            $this->applier->setIsTaxChanged($this->quote)
        );
    }

    /**
     * Test setIsAddressChanged
     */
    public function testSetIsAddressChanged()
    {
        $this->assertInstanceOf(
            Applier::class,
            $this->applier->setIsAddressChanged($this->quote)
        );
    }

    /**
     * Test removeMessage
     */
    public function testRemoveMessage()
    {
        /**
         * @var NegotiableQuoteInterface|MockObject $negotiableQuote
         */
        $negotiableQuote = $this->getMockForAbstractClass(NegotiableQuoteInterface::class);
        $negotiableQuote->expects($this->any())->method('getNotifications')->willReturn(256);
        $this->appState->expects($this->any())->method('getAreaCode')
            ->willReturn(Area::AREA_ADMINHTML);

        $this->assertNull($this->applier->removeMessage($negotiableQuote, 1, true));
    }

    /**
     * DataProvider setHasItemChanges
     *
     * @return array
     */
    public function dataProviderSetHasItemChanges()
    {
        return [
            [1.00],
            [null]
        ];
    }
}
