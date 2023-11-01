<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Validator;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\NegotiableQuote\Helper\Config;
use Magento\NegotiableQuote\Model\Validator\QuoteTotals;
use Magento\NegotiableQuote\Model\Validator\ValidatorResult;
use Magento\NegotiableQuote\Model\Validator\ValidatorResultFactory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CurrencyInterface;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for QuoteTotals.
 */
class QuoteTotalsTest extends TestCase
{
    /**
     * @var Config|MockObject
     */
    private $configHelper;

    /**
     * @var ValidatorResultFactory|MockObject
     */
    private $validatorResultFactory;

    /**
     * @var QuoteTotals
     */
    private $quoteTotals;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->configHelper = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validatorResultFactory = $this
            ->getMockBuilder(ValidatorResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->quoteTotals = $objectManagerHelper->getObject(
            QuoteTotals::class,
            [
                'configHelper' => $this->configHelper,
                'validatorResultFactory' => $this->validatorResultFactory,
            ]
        );
    }

    /**
     * Test for validate().
     *
     * @param string $minimumAmountMessage
     * @param int $getMinimumAmountInvokesCount
     * @param int $getQuoteCurrencyCodeInvokesCount
     * @return void
     * @dataProvider validateDataProvider
     */
    public function testValidate(
        $minimumAmountMessage,
        $getMinimumAmountInvokesCount,
        $getQuoteCurrencyCodeInvokesCount
    ) {
        $result = $this->getMockBuilder(ValidatorResult::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validatorResultFactory->expects($this->atLeastOnce())->method('create')->willReturn($result);
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quote->expects($this->atLeastOnce())->method('getItemsCount')->willReturn(1);
        $this->configHelper->expects($this->atLeastOnce())->method('isQuoteAllowed')->with($quote)->willReturn(false);
        $this->configHelper->expects($this->atLeastOnce())->method('getMinimumAmountMessage')
            ->willReturn($minimumAmountMessage);
        $this->configHelper->expects($this->exactly($getMinimumAmountInvokesCount))->method('getMinimumAmount')
            ->willReturn(1.00);
        $currency = $this->getMockBuilder(CurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $currency->expects($this->exactly($getQuoteCurrencyCodeInvokesCount))->method('getQuoteCurrencyCode')
            ->willReturn('USD');
        $quote->expects($this->exactly($getQuoteCurrencyCodeInvokesCount))->method('getCurrency')
            ->willReturn($currency);
        $result->expects($this->atLeastOnce())->method('addMessage')->willReturnSelf();

        $this->assertInstanceOf(
            ValidatorResult::class,
            $this->quoteTotals->validate(['quote' => $quote])
        );
    }

    /**
     * Test for validate() with empty quote data.
     *
     * @return void
     */
    public function testValidateWithEmptyQuote()
    {
        $result = $this->getMockBuilder(ValidatorResult::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validatorResultFactory->expects($this->atLeastOnce())->method('create')->willReturn($result);
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quote->expects($this->never())->method('getItemsCount');

        $this->assertInstanceOf(
            ValidatorResult::class,
            $this->quoteTotals->validate([])
        );
    }

    /**
     * Test for validate() for quote without quote items.
     *
     * @return void
     */
    public function testValidateWithEmptyQuoteItems()
    {
        $result = $this->getMockBuilder(ValidatorResult::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validatorResultFactory->expects($this->atLeastOnce())->method('create')->willReturn($result);
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quote->expects($this->atLeastOnce())->method('getItemsCount')->willReturn(0);
        $result->expects($this->atLeastOnce())->method('addMessage')->willReturnSelf();
        $this->configHelper->expects($this->never())->method('isQuoteAllowed');

        $this->assertInstanceOf(
            ValidatorResult::class,
            $this->quoteTotals->validate(['quote' => $quote])
        );
    }

    /**
     * DataProvider for validate().
     *
     * @return array
     */
    public function validateDataProvider()
    {
        return [
            ['message', 0, 0],
            ['', 1, 1]
        ];
    }
}
