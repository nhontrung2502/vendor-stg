<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Plugin\Quote\Api;

use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface;
use Magento\NegotiableQuote\Model\NegotiableQuoteConverter;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\NegotiableQuote\Plugin\Quote\Api\ProcessNegotiableQuotePlugin;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\NegotiableQuote\Plugin\Quote\Api\ProcessNegotiableQuotePlugin class.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProcessNegotiableQuotePluginTest extends TestCase
{
    /**
     * @var NegotiableQuoteItemManagementInterface|MockObject
     */
    private $quoteItemManagement;

    /**
     * @var RestrictionInterface|MockObject
     */
    private $restriction;

    /**
     * @var NegotiableQuoteConverter|MockObject
     */
    private $negotiableQuoteConverter;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializer;

    /**
     * @var ProcessNegotiableQuotePlugin
     */
    private $plugin;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->quoteItemManagement = $this->getMockBuilder(
            NegotiableQuoteItemManagementInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->restriction = $this->getMockBuilder(
            RestrictionInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->negotiableQuoteConverter = $this->getMockBuilder(
            NegotiableQuoteConverter::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->serializer = $this->getMockBuilder(
            SerializerInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->plugin = $objectManager->getObject(
            ProcessNegotiableQuotePlugin::class,
            [
                'quoteItemManagement' => $this->quoteItemManagement,
                'restriction' => $this->restriction,
                'negotiableQuoteConverter' => $this->negotiableQuoteConverter,
                'serializer' => $this->serializer,
            ]
        );
    }

    /**
     * Test for afterGet method.
     *
     * @param bool $quoteCanBeSubmitted
     * @param float|null $negotiatedPrice
     * @return void
     * @dataProvider afterGetDataProvider
     */
    public function testAfterGet($quoteCanBeSubmitted, $negotiatedPrice)
    {
        $subject = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $result = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        if ($quoteCanBeSubmitted) {
            $quote = $this->processQuoteIfCanSubmit($result);
        } else {
            $quote = $this->processQuoteIfCantSubmit($result, $negotiatedPrice);
        }

        $this->assertEquals($quote, $this->plugin->afterGet($subject, $result));

        // test result caching
        $this->negotiableQuoteConverter->expects($this->never())
            ->method('arrayToQuote');
        $this->assertEquals($quote, $this->plugin->afterGet($subject, $result));
    }

    /**
     * Test for afterGetList method.
     *
     * @return void
     */
    public function testAfterGetList()
    {
        $subject = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $result = $this->getMockBuilder(SearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $result->expects($this->once())->method('getItems')->willReturn([$item]);
        $quote = $this->processQuoteIfCanSubmit($item);
        $result->expects($this->once())->method('setItems')->with([$quote])->willReturnSelf();

        $this->assertEquals($result, $this->plugin->afterGetList($subject, $result));
    }

    /**
     * Process quote if it can't be submitted.
     *
     * @param MockObject $quote
     * @return MockObject
     */
    private function processQuoteIfCanSubmit(MockObject $quote)
    {
        $quoteId = 1;
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();
        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSnapshot'])
            ->getMockForAbstractClass();
        $quote->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $extensionAttributes->expects($this->atLeastOnce())->method('getNegotiableQuote')->willReturn($negotiableQuote);
        $negotiableQuote->expects($this->atLeastOnce())->method('getIsRegularQuote')->willReturn(true);
        $this->restriction->expects($this->atLeastOnce())->method('setQuote')->with($quote)->willReturnSelf();
        $quote->expects($this->atLeastOnce())->method('getId')->willReturn($quoteId);
        $this->restriction->expects($this->atLeastOnce())->method('canSubmit')->willReturn(false);
        $negotiableQuote->expects($this->atLeastOnce())
            ->method('getSnapshot')
            ->willReturn('serialized data');
        $this->serializer->expects($this->once())
            ->method('unserialize')
            ->willReturn(['quote_snapshot' => 'quote_data']);
        $this->negotiableQuoteConverter->expects($this->once())
            ->method('arrayToQuote')
            ->with(['quote_snapshot' => 'quote_data'])
            ->willReturn($quote);
        return $quote;
    }

    /**
     * Process quote if it can be submitted.
     *
     * @param MockObject $quote
     * @param float|null $negotiatedPrice
     * @return MockObject
     */
    private function processQuoteIfCantSubmit(MockObject $quote, $negotiatedPrice)
    {
        $quoteId = 1;
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();
        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSnapshot'])
            ->getMockForAbstractClass();
        $quote->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $extensionAttributes->expects($this->atLeastOnce())->method('getNegotiableQuote')->willReturn($negotiableQuote);
        $negotiableQuote->expects($this->atLeastOnce())->method('getIsRegularQuote')->willReturn(true);
        $this->restriction->expects($this->atLeastOnce())->method('setQuote')->with($quote)->willReturnSelf();
        $quote->expects($this->atLeastOnce())->method('getId')->willReturn($quoteId);
        $this->restriction->expects($this->atLeastOnce())->method('canSubmit')->willReturn(true);
        $negotiableQuote->expects($this->atLeastOnce())
            ->method('getNegotiatedPriceValue')
            ->willReturn($negotiatedPrice);
        if ($negotiatedPrice) {
            $this->quoteItemManagement->expects($this->once())
                ->method('updateQuoteItemsCustomPrices')
                ->with($quoteId, false)
                ->willReturn(true);
        } else {
            $this->quoteItemManagement->expects($this->once())
                ->method('recalculateOriginalPriceTax')
                ->with($quoteId, true, true, false, false)
                ->willReturn(true);
        }

        return $quote;
    }

    /**
     * Data provider for afterGet method.
     *
     * @return array
     */
    public function afterGetDataProvider()
    {
        return [
            [true, null],
            [false, null],
            [false, 100.5]
        ];
    }
}
