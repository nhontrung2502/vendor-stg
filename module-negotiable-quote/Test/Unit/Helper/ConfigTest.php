<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Helper\Config;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var Config
     */
    protected $helper;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $configMock;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $className = Config::class;
        $arguments = $objectManagerHelper->getConstructArguments($className);
        /** @var Context $context */
        $context = $arguments['context'];
        $this->configMock = $context->getScopeConfig();
        /** @var Context $context */
        $this->helper = $objectManagerHelper->getObject($className, $arguments);
    }

    /**
     * @covers \Magento\NegotiableQuote\Helper\Config::isQuoteAllowed
     * @param $isAllowExpected
     * @dataProvider isQuoteAllowDataProvider
     */
    public function testIsQuoteAllow($isAllowExpected)
    {
        $quoteMock =
            $this->getMockBuilder(Quote::class)
                ->addMethods(['getSubtotalWithDiscount'])
                ->onlyMethods(['getStoreId'])
                ->disableOriginalConstructor()
                ->getMock();

        /** @var Config $helperMock */
        $helperMock = $this->createPartialMock(Config::class, ['isAllowedAmount']);

        $helperMock->expects($this->once())->method('isAllowedAmount')->willReturn($isAllowExpected);

        $isAllow = $helperMock->isQuoteAllowed($quoteMock);
        $this->assertEquals($isAllowExpected, $isAllow);
    }

    /**
     * @return array
     */
    public function isQuoteAllowDataProvider()
    {
        return [
            [false],
            [true]
        ];
    }

    /**
     * @covers \Magento\NegotiableQuote\Helper\Config::isAllowedAmount
     * @dataProvider getIsAllowAmountProvider
     * @param $amount
     * @param $minimumAmount
     * @param $expectedIsAllow
     */
    public function testIsAllowAmount($amount, $minimumAmount, $expectedIsAllow)
    {
        $helperMock = $this->createPartialMock(
            Config::class,
            ['getMinimumAmount']
        );

        $helperMock->expects($this->once())->method('getMinimumAmount')->willReturn($minimumAmount);

        $isAllow = $helperMock->isAllowedAmount($amount);
        $this->assertEquals($expectedIsAllow, $isAllow);
    }

    /**
     * @return array
     */
    public function getIsAllowAmountProvider()
    {
        return [
            [10, 100, false],
            [100, 100, true],
            [110, 100, true]
        ];
    }

    /**
     * @covers \Magento\NegotiableQuote\Helper\Config::getMinimumAmount
     * @dataProvider getMinimumAmountProvider
     */
    public function testGetMinimumAmount($configValue, $expectedAmount)
    {
        $this->configMock->expects($this->any())->method('getValue')
            ->with('quote/general/minimum_amount')
            ->willReturn($configValue);

        $amount = $this->helper->getMinimumAmount();

        $this->assertEquals($amount, $expectedAmount);
    }

    /**
     * @return array
     */
    public function getMinimumAmountProvider()
    {
        return [
            ['100', 100],
            ['', 0],
            ['100.5464', 100.5464],
            ['not_number', 0]
        ];
    }
}
