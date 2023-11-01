<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Plugin\Quote\Model\Cart\Totals;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteItemInterface;
use Magento\NegotiableQuote\Plugin\Quote\Model\Cart\Totals\ItemConverterPlugin;
use Magento\Quote\Api\Data\CartItemExtensionInterface;
use Magento\Quote\Model\Cart\Totals\ItemConverter;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\TestCase;

class ItemConverterPluginTest extends TestCase
{
    /**
     * @var ItemConverterPlugin
     */
    private $plugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->plugin = $objectManager->getObject(
            ItemConverterPlugin::class
        );
    }

    /**
     * Test for beforeModelToDataObject method.
     *
     * @return void
     */
    public function testBeforeModelToDataObject()
    {
        $itemConverter = $this->createMock(ItemConverter::class);
        $item = $this->createMock(Item::class);
        $itemExtensionAttributes = $this->getMockForAbstractClass(
            CartItemExtensionInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getNegotiableQuoteItem']
        );
        $negotiableItem = $this->getMockForAbstractClass(NegotiableQuoteItemInterface::class);
        $item->expects($this->exactly(2))->method('getExtensionAttributes')->willReturn($itemExtensionAttributes);
        $itemExtensionAttributes->expects($this->once())->method('getNegotiableQuoteItem')->willReturn($negotiableItem);
        $item->expects($this->once())->method('setData')->with('extension_attributes', null)->willReturnSelf();
        $this->plugin->beforeModelToDataObject($itemConverter, $item);
    }
}
