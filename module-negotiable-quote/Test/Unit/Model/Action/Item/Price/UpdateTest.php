<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Action\Item\Price;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\Action\Item\Price\Update;
use Magento\Quote\Api\Data\CartItemInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Update model.
 */
class UpdateTest extends TestCase
{
    /**
     * @var FormatInterface|MockObject
     */
    private $localeFormat;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializer;

    /**
     * @var Update
     */
    private $quoteItemPriceUpdater;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->localeFormat = $this->getMockBuilder(FormatInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->serializer = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->quoteItemPriceUpdater = $objectManager->getObject(
            Update::class,
            [
                'localeFormat' => $this->localeFormat,
                'serializer' => $this->serializer,
            ]
        );
    }

    /**
     * Test for update method.
     *
     * @return void
     */
    public function testUpdate()
    {
        $customPrice = 15;
        $buyRequestData = ['buy_request_data'];
        $item = $this->getMockBuilder(CartItemInterface::class)
            ->setMethods(
                [
                    'getBuyRequest',
                    'getProduct',
                    'addOption',
                    'setCustomPrice',
                    'setOriginalCustomPrice',
                    'setNoDiscount'
                ]
            )->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->localeFormat->expects($this->once())->method('getNumber')->with($customPrice)->willReturnArgument(0);
        $buyRequest = $this->getMockBuilder(DataObject::class)
            ->setMethods(['setCustomPrice', 'setValue', 'setCode', 'setProduct', 'getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $item->expects($this->once())->method('getBuyRequest')->willReturn($buyRequest);
        $buyRequest->expects($this->once())->method('setCustomPrice')->with($customPrice)->willReturnSelf();
        $buyRequest->expects($this->once())->method('getData')->willReturn($buyRequestData);
        $this->serializer->expects($this->once())
            ->method('serialize')->with($buyRequestData)->willReturn(json_encode($buyRequestData));
        $buyRequest->expects($this->once())->method('setValue')->with(json_encode($buyRequestData))->willReturnSelf();
        $buyRequest->expects($this->once())->method('setCode')->with('info_buyRequest')->willReturnSelf();
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item->expects($this->once())->method('getProduct')->willReturn($product);
        $buyRequest->expects($this->once())->method('setProduct')->with($product)->willReturnSelf();
        $item->expects($this->once())->method('addOption')->with($buyRequest)->willReturnSelf();
        $item->expects($this->once())->method('setCustomPrice')->with($customPrice)->willReturnSelf();
        $item->expects($this->once())->method('setOriginalCustomPrice')->with($customPrice)->willReturnSelf();
        $item->expects($this->once())->method('setNoDiscount')->with(true)->willReturnSelf();
        $this->quoteItemPriceUpdater->update($item, ['custom_price' => $customPrice]);
    }

    /**
     * Test for update method with empty custom price.
     *
     * @return void
     */
    public function testUpdateWithEmptyCustomPrice()
    {
        $customPrice = null;
        $buyRequestData = ['buy_request_data'];
        $item = $this->getMockBuilder(CartItemInterface::class)
            ->setMethods(
                [
                    'hasData',
                    'getBuyRequest',
                    'getProduct',
                    'addOption',
                    'unsetData',
                    'setNoDiscount'
                ]
            )->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item->expects($this->once())->method('hasData')->with('custom_price')->willReturn(true);
        $buyRequest = $this->getMockBuilder(DataObject::class)
            ->setMethods(['unsetData', 'setValue', 'setCode', 'setProduct', 'getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $item->expects($this->once())->method('getBuyRequest')->willReturn($buyRequest);
        $buyRequest->expects($this->once())->method('unsetData')->with('custom_price')->willReturnSelf();
        $buyRequest->expects($this->once())->method('getData')->willReturn($buyRequestData);
        $this->serializer->expects($this->once())
            ->method('serialize')->with($buyRequestData)->willReturn(json_encode($buyRequestData));
        $buyRequest->expects($this->once())->method('setValue')->with(json_encode($buyRequestData))->willReturnSelf();
        $buyRequest->expects($this->once())->method('setCode')->with('info_buyRequest')->willReturnSelf();
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item->expects($this->once())->method('getProduct')->willReturn($product);
        $buyRequest->expects($this->once())->method('setProduct')->with($product)->willReturnSelf();
        $item->expects($this->once())->method('addOption')->with($buyRequest)->willReturnSelf();
        $item->expects($this->atLeastOnce())
            ->method('unsetData')->withConsecutive(['custom_price'], ['original_custom_price'])->willReturnSelf();
        $item->expects($this->once())->method('setNoDiscount')->with(false)->willReturnSelf();
        $this->quoteItemPriceUpdater->update($item, ['custom_price' => $customPrice, 'use_discount' => true]);
    }
}
