<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Plugin\Quote\Api;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterfaceFactory;
use Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface;
use Magento\NegotiableQuote\Model\ResourceModel\NegotiableQuote;
use Magento\NegotiableQuote\Plugin\Quote\Api\NegotiableQuoteRecalculate;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\NegotiableQuote\Plugin\Quote\Api\NegotiableQuoteRecalculate class.
 */
class NegotiableQuoteRecalculateTest extends TestCase
{
    /**
     * @var NegotiableQuoteItemManagementInterface|MockObject
     */
    private $quoteItemManagement;

    /**
     * @var NegotiableQuoteInterfaceFactory|MockObject
     */
    private $negotiableQuoteFactory;

    /**
     * @var NegotiableQuote|MockObject
     */
    private $negotiableQuoteResource;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var NegotiableQuoteRecalculate
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
        $this->negotiableQuoteFactory = $this->getMockBuilder(
            NegotiableQuoteInterfaceFactory::class
        )
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->negotiableQuoteResource = $this->getMockBuilder(
            NegotiableQuote::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteRepository = $this->getMockBuilder(
            CartRepositoryInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quotesForRecalculate = ["1" => true, "2" => false];

        $objectManager = new ObjectManager($this);
        $this->plugin = $objectManager->getObject(
            NegotiableQuoteRecalculate::class,
            [
                'quoteItemManagement' => $this->quoteItemManagement,
                'negotiableQuoteFactory' => $this->negotiableQuoteFactory,
                'negotiableQuoteResource' => $this->negotiableQuoteResource,
                'quotesForRecalculate' => $quotesForRecalculate,
            ]
        );
    }

    /**
     * Test for beforeSave and afterSave methods.
     *
     * @param int $quoteId
     * @param string $newData
     * @param $recalculateExpected
     * @param string $recalculateMethod
     * @param int $price
     * @param $validateExpected
     * @return void
     * @dataProvider saveDataProvider
     */
    public function testSave(
        $quoteId,
        $newData,
        $recalculateExpected,
        $recalculateMethod,
        $price,
        $validateExpected
    ) {
        $subject = $this->quoteRepository;
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();
        $negotiableQuote = $this->getMockBuilder(\Magento\NegotiableQuote\Model\NegotiableQuote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quote->expects($validateExpected)->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $quote->expects($this->atLeastOnce())->method('getId')->willReturn($quoteId);
        $extensionAttributes->expects($validateExpected)->method('getNegotiableQuote')
            ->willReturn($negotiableQuote);
        $negotiableQuote->expects($validateExpected)->method('hasData')->willReturn(true);
        $negotiableQuote->expects($validateExpected)->method('getData')->willReturn('data');
        $negotiableQuote->expects($recalculateExpected)->method('getNegotiatedPriceValue')->willReturn($price);

        $initialNegotiableQuote = $this->getMockBuilder(
            \Magento\NegotiableQuote\Model\NegotiableQuote::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->negotiableQuoteFactory->expects($validateExpected)->method('create')
            ->willReturn($initialNegotiableQuote);
        $initialNegotiableQuote->expects($validateExpected)->method('getQuoteId')->willReturn(3);
        $initialNegotiableQuote->expects($validateExpected)->method('getIsRegularQuote')->willReturn(true);
        $initialNegotiableQuote->expects($validateExpected)->method('getData')->willReturn($newData);

        $this->quoteItemManagement->expects($recalculateExpected)->method($recalculateMethod);

        $this->assertEquals([$quote], $this->plugin->beforeSave($subject, $quote));
        $this->plugin->afterSave($subject, null, $quote);
    }

    /**
     * Data provider for testSave method.
     *
     * @return array
     */
    public function saveDataProvider()
    {
        return [
            [3, 'new_data', $this->atLeastOnce(), 'updateQuoteItemsCustomPrices', 10, $this->atLeastOnce()],
            [3, 'new_data', $this->atLeastOnce(), 'recalculateOriginalPriceTax', null, $this->atLeastOnce()],
            [3, 'data', $this->never(), 'recalculateOriginalPriceTax', null, $this->atLeastOnce()],
            [2, 'data', $this->never(), 'recalculateOriginalPriceTax', null, $this->never()],
            [1, 'data', $this->atLeastOnce(), 'recalculateOriginalPriceTax', null, $this->atLeastOnce()],
        ];
    }
}
