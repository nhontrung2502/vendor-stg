<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Validator;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterfaceFactory;
use Magento\NegotiableQuote\Model\ResourceModel\NegotiableQuote;
use Magento\NegotiableQuote\Model\Validator\NegotiablePrice;
use Magento\NegotiableQuote\Model\Validator\ValidatorResult;
use Magento\NegotiableQuote\Model\Validator\ValidatorResultFactory;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for \Magento\NegotiableQuote\Model\Validator\NegotiablePrice class.
 */
class NegotiablePriceTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var NegotiablePrice
     */
    private $negotiablePrice;

    /**
     * @var NegotiableQuoteInterfaceFactory|MockObject
     */
    private $negotiableQuoteFactoryMock;

    /**
     * @var NegotiableQuote|MockObject
     */
    private $negotiableQuoteResourceMock;

    /**
     * @var ValidatorResultFactory|MockObject
     */
    private $validatorResultFactoryMock;

    /**
     * @var ValidatorResult|MockObject
     */
    private $resultMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->negotiableQuoteFactoryMock = $this->getMockBuilder(NegotiableQuoteInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->negotiableQuoteResourceMock = $this->getMockBuilder(NegotiableQuote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validatorResultFactoryMock = $this->getMockBuilder(ValidatorResultFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultMock = $this->getMockBuilder(ValidatorResult::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->validatorResultFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->resultMock);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->negotiablePrice = $this->objectManagerHelper->getObject(
            NegotiablePrice::class,
            [
                'negotiableQuoteFactory' => $this->negotiableQuoteFactoryMock,
                'negotiableQuoteResource' => $this->negotiableQuoteResourceMock,
                'validatorResultFactory' => $this->validatorResultFactoryMock
            ]
        );
    }

    /**
     * Test validate without any quote.
     *
     * @return void
     */
    public function testValidateWithoutAnyQuote()
    {
        $this->resultMock->expects($this->never())
            ->method('addMessage');
        $this->assertInstanceOf(
            ValidatorResult::class,
            $this->negotiablePrice->validate([])
        );
    }

    /**
     * Test validate without negotiable quote instance.
     *
     * @return void
     */
    public function testValidateWithoutNegotiableQuote()
    {
        $negotiableQuote = $this->buildNegotiableQuoteMock();
        $quoteMock = $this->buildQuoteMock($negotiableQuote);

        $oldNegotiableQuote = $this->getMockBuilder(\Magento\NegotiableQuote\Model\NegotiableQuote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->negotiableQuoteFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($oldNegotiableQuote);
        $this->negotiableQuoteResourceMock->expects($this->atLeastOnce())
            ->method('load')
            ->willReturn($oldNegotiableQuote);

        $this->resultMock->expects($this->never())
            ->method('addMessage');

        $this->assertInstanceOf(
            ValidatorResult::class,
            $this->negotiablePrice->validate([
                'quote' => $quoteMock
            ])
        );
    }

    /**
     * Test validate without type.
     *
     * @return void
     */
    public function testValidateWithoutType()
    {
        $negotiableQuote = $this->buildNegotiableQuoteMock();
        $quoteMock = $this->buildQuoteMock($negotiableQuote);

        $oldNegotiableQuote = $this->getMockBuilder(\Magento\NegotiableQuote\Model\NegotiableQuote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->negotiableQuoteFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($oldNegotiableQuote);
        $this->negotiableQuoteResourceMock->expects($this->atLeastOnce())
            ->method('load')
            ->willReturn($oldNegotiableQuote);

        $oldNegotiableQuote->expects($this->atLeastOnce())
            ->method('getData')
            ->willReturnMap([
                ['negotiated_price_value', null, 1]
            ]);

        $this->resultMock->expects($this->once())
            ->method('addMessage');

        $this->assertInstanceOf(
            ValidatorResult::class,
            $this->negotiablePrice->validate([
                'quote' => $quoteMock
            ])
        );
    }

    /**
     * Test validate without price value.
     *
     * @return void
     */
    public function testValidateWithoutPriceValue()
    {
        $negotiableQuote = $this->buildNegotiableQuoteMock();
        $quoteMock = $this->buildQuoteMock($negotiableQuote);

        $oldNegotiableQuote = $this->getMockBuilder(\Magento\NegotiableQuote\Model\NegotiableQuote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->negotiableQuoteFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($oldNegotiableQuote);
        $this->negotiableQuoteResourceMock->expects($this->atLeastOnce())
            ->method('load')
            ->willReturn($oldNegotiableQuote);

        $oldNegotiableQuote->expects($this->atLeastOnce())
            ->method('getNegotiatedPriceType')
            ->willReturn(2);
        $negotiableQuote->expects($this->atLeastOnce())
            ->method('getNegotiatedPriceType')
            ->willReturn(1);
        $negotiableQuote->expects($this->atLeastOnce())
            ->method('hasData')
            ->willReturnMap([
                ['negotiated_price_value', false]
            ]);

        $this->resultMock->expects($this->once())
            ->method('addMessage');

        $this->assertInstanceOf(
            ValidatorResult::class,
            $this->negotiablePrice->validate([
                'quote' => $quoteMock
            ])
        );
    }

    /**
     * Test validate with incorrect price type.
     *
     * @return void
     */
    public function testValidateWithIncorrectPriceType()
    {
        $negotiableQuote = $this->buildNegotiableQuoteMock();
        $quoteMock = $this->buildQuoteMock($negotiableQuote);

        $oldNegotiableQuote = $this->getMockBuilder(\Magento\NegotiableQuote\Model\NegotiableQuote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->negotiableQuoteFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($oldNegotiableQuote);
        $this->negotiableQuoteResourceMock->expects($this->atLeastOnce())
            ->method('load')
            ->willReturn($oldNegotiableQuote);

        $oldNegotiableQuote->expects($this->atLeastOnce())
            ->method('getData')
            ->willReturnMap([
                ['negotiated_price_type', null, 77]
            ]);

        $this->resultMock->expects($this->once())
            ->method('addMessage');

        $this->assertInstanceOf(
            ValidatorResult::class,
            $this->negotiablePrice->validate([
                'quote' => $quoteMock
            ])
        );
    }

    /**
     * Test validate with incorrect price value.
     *
     * @param int $priceType
     * @param float $priceValue
     * @return void
     * @dataProvider validateWithIncorrectPriceDataProvider
     */
    public function testValidateWithIncorrectPrice($priceType, $priceValue)
    {
        $negotiableQuote = $this->buildNegotiableQuoteMock();
        $quoteMock = $this->buildQuoteMock($negotiableQuote);

        $oldNegotiableQuote = $this->getMockBuilder(\Magento\NegotiableQuote\Model\NegotiableQuote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->negotiableQuoteFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($oldNegotiableQuote);
        $this->negotiableQuoteResourceMock->expects($this->atLeastOnce())
            ->method('load')
            ->willReturn($oldNegotiableQuote);

        $negotiableQuote->expects($this->atLeastOnce())
            ->method('hasData')
            ->willReturnMap([
                ['negotiated_price_type', true],
                ['negotiated_price_value', true]
            ]);
        $negotiableQuote->expects($this->atLeastOnce())
            ->method('getData')
            ->willReturnMap([
                ['negotiated_price_type', null, $priceType],
                ['negotiated_price_value', null, $priceValue]
            ]);

        $this->resultMock->expects($this->once())
            ->method('addMessage');

        $this->assertInstanceOf(
            ValidatorResult::class,
            $this->negotiablePrice->validate([
                'quote' => $quoteMock
            ])
        );
    }

    /**
     * Data provider for testValidateWithIncorrectPrice.
     *
     * @return array
     */
    public function validateWithIncorrectPriceDataProvider()
    {
        return [
            [1, -1],
            [1, 111],
            [2, 100]
        ];
    }

    /**
     * Build quote mock.
     *
     * @param MockObject $negotiableQuoteMock
     * @return MockObject
     */
    private function buildQuoteMock(MockObject $negotiableQuoteMock)
    {
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();
        $extensionAttributes->expects($this->atLeastOnce())
            ->method('getNegotiableQuote')
            ->willReturn($negotiableQuoteMock);
        $quoteMock = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quoteMock->expects($this->atLeastOnce())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);

        return $quoteMock;
    }

    /**
     * Build negotiable quote mock.
     *
     * @return MockObject
     */
    private function buildNegotiableQuoteMock()
    {
        $negotiableQuote = $this->getMockBuilder(\Magento\NegotiableQuote\Model\NegotiableQuote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $negotiableQuote->expects($this->atLeastOnce())
            ->method('getIsRegularQuote')
            ->willReturn(true);

        return $negotiableQuote;
    }
}
