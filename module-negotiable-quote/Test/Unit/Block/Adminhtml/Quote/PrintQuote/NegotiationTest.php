<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Block\Adminhtml\Quote\PrintQuote;

use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\LayoutInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Block\Adminhtml\Quote\PrintQuote\Negotiation;
use Magento\NegotiableQuote\Block\Adminhtml\Quote\View\Totals;
use PHPUnit\Framework\TestCase;

class NegotiationTest extends TestCase
{
    /**
     * @var Negotiation
     */
    protected $negotiation;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->negotiation = $objectManager->getObject(
            Negotiation::class,
            []
        );
    }

    /**
     * Tests getTotalOptions() method
     *
     * @dataProvider getTotalOptionsDataProvider
     * @param int $type
     * @param string $expectedValue
     * @return void
     */
    public function testGetTotalOptions($type, $expectedValue)
    {
        $layout = $this->getMockForAbstractClass(LayoutInterface::class);
        $layout->expects($this->any())->method('getParentName')->willReturn('parent');
        $parent = $this->createMock(Totals::class);
        $totals = [
            'negotiation' => new DataObject(
                [
                    'code' => NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE,
                    'code_value' => NegotiableQuoteInterface::NEGOTIATED_PRICE_VALUE,
                    'value' => 5,
                    'type' => $type
                ]
            ),
            'catalog_price' => new DataObject(
                [
                    'value' => 20,
                ]
            ),
        ];
        $parent->expects($this->any())->method('getTotals')->willReturn($totals);
        $layout->expects($this->any())->method('getBlock')->willReturn($parent);

        $this->negotiation->setLayout($layout);
        $totals = $this->negotiation->getTotalOptions();
        $this->assertEquals($expectedValue, $totals['proposed']->getValue());
    }

    /**
     * Data provider for testGetTotalOptions
     *
     * @return array
     */
    public function getTotalOptionsDataProvider()
    {
        return [
            [NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE_PERCENTAGE_DISCOUNT, 19],
            [NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE_AMOUNT_DISCOUNT, 15],
            [NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE_PROPOSED_TOTAL, 5],
        ];
    }
}
