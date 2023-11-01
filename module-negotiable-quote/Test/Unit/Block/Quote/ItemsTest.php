<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Block\Quote;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\RendererList;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Block\Quote\Items;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ItemsTest extends TestCase
{
    /**
     * @var Items
     */
    protected $itemsBlock;

    /**
     * @var Quote|MockObject
     */
    protected $quote;

    /**
     * @var Item|MockObject
     */
    protected $quoteItem;

    /**
     * @var \Magento\NegotiableQuote\Helper\Quote|MockObject
     */
    protected $negotiableQuoteHelper;

    /**
     * @var NegotiableQuoteInterface
     */
    private $negotiableQuote;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->quote = $this->createMock(Quote::class);
        $this->quoteItem =  $this->createMock(Item::class);
        $this->quote->expects($this->any())
            ->method('getAllVisibleItems')->willReturn([$this->quoteItem]);

        $this->negotiableQuoteHelper = $this->createMock(\Magento\NegotiableQuote\Helper\Quote::class);
        $this->negotiableQuoteHelper->expects($this->any())->method('resolveCurrentQuote')
            ->willReturn($this->quote);

        $objectManager = new ObjectManager($this);
        $this->itemsBlock = $objectManager->getObject(
            Items::class,
            [
                'negotiableQuoteHelper' => $this->negotiableQuoteHelper,
                'data' => []
            ]
        );

        $layout = $this->getMockForAbstractClass(LayoutInterface::class);
        $this->itemsBlock->setLayout($layout);
    }

    /**
     * Test for method getItems()
     *
     * @return void
     */
    public function testGetItems()
    {
        $this->assertEquals([$this->quoteItem], $this->itemsBlock->getItems());
    }

    /**
     * @dataProvider dataForItemHtml
     *
     * @param string $productType
     * @param string $listName
     * @param bool $isRenderer
     * @param bool $expectedResult
     */
    public function testGetItemHtml($productType, $listName, $isRenderer, $expectedResult)
    {
        $this->quoteItem->expects($this->any())
            ->method('getProductType')->willReturn($productType);
        $this->itemsBlock->setRendererListName($listName);

        $rendererList =  null;
        if ($isRenderer) {
            $rendererList = $this->createPartialMock(
                RendererList::class,
                ['getRenderer']
            );
            $renderer = $this->getMockBuilder(Template::class)
                ->addMethods(['setItem'])
                ->onlyMethods(['toHtml'])
                ->disableOriginalConstructor()
                ->getMock();
            $renderer->expects($this->any())
                ->method('toHtml')->willReturn('rendered');
            $renderer->expects($this->any())
                ->method('setItem')->willReturnSelf();
            $rendererList->expects($this->any())
                ->method('getRenderer')->willReturn($renderer);
        }
        if ($listName) {
            $this->itemsBlock->getLayout()->expects($this->any())
                ->method('getBlock')->with($listName)->willReturn($rendererList);
        } else {
            $this->itemsBlock->getLayout()->expects($this->any())
                ->method('getChildName')->willReturn('child');
            $this->itemsBlock->getLayout()->expects($this->any())
                ->method('getBlock')->with('child')->willReturn($rendererList);
        }

        try {
            $result = $this->itemsBlock->getItemHtml($this->quoteItem);
        } catch (\Exception $e) {
            $result = 'error';
        }

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return void
     */
    private function prepareQuote()
    {
        $this->negotiableQuote = $this
            ->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getNegotiatedPriceValue',
                'getStatus',
                'getShippingPrice',
                'getIsCustomerPriceChanged',
                'getIsShippingTaxChanged'
            ])
            ->getMockForAbstractClass();
        $extensionAttributes = $this
            ->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();
        $this->quote->expects($this->atLeastOnce())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        $extensionAttributes->expects($this->any())
            ->method('getNegotiableQuote')
            ->willReturn($this->negotiableQuote);
        $this->negotiableQuote->expects($this->once())
            ->method('getStatus')
            ->willReturn(NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN);
    }

    /**
     * @return void
     */
    public function testIsCustomerPriceChanged()
    {
        $this->prepareQuote();
        $this->negotiableQuote->expects($this->atLeastOnce())
            ->method('getNegotiatedPriceValue')
            ->willReturn(1);
        $this->negotiableQuote->expects($this->once())
            ->method('getIsCustomerPriceChanged')
            ->willReturn(true);
        $this->assertTrue($this->itemsBlock->isCustomerPriceChanged());
    }

    /**
     * @return void
     */
    public function testIsShippingTaxChanged()
    {
        $this->prepareQuote();
        $this->negotiableQuote->expects($this->atLeastOnce())
            ->method('getShippingPrice')
            ->willReturn(1);
        $this->negotiableQuote->expects($this->once())
            ->method('getIsShippingTaxChanged')
            ->willReturn(true);
        $this->assertTrue($this->itemsBlock->isShippingTaxChanged());
    }

    /**
     * Data provider for testGetItemHtml
     *
     * @return array
     */
    public function dataForItemHtml()
    {
        return [
            ['simple', 'renderer', false, 'error'],
            ['simple', 'renderer', true, 'rendered'],
            [null, null, true, 'rendered'],
        ];
    }
}
