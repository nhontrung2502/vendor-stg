<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Plugin\Quote\Model\Quote;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\NegotiableQuote;
use Magento\NegotiableQuote\Model\Plugin\Quote\Model\Quote\AddressPlugin;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Rate;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for \Magento\NegotiableQuote\Model\Plugin\Quote\Model\Quote\AddressPlugin.
 */
class AddressPluginTest extends TestCase
{
    /**
     * @var State|MockObject
     */
    private $appState;

    /**
     * @var AddressPlugin
     */
    private $addressPlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->appState =
            $this->getMockBuilder(State::class)
                ->disableOriginalConstructor()
                ->getMock();
        $objectManager = new ObjectManager($this);
        $this->addressPlugin = $objectManager->getObject(
            AddressPlugin::class,
            [
                'appState' => $this->appState
            ]
        );
    }

    /**
     * Test for afterRequestShippingRates().
     *
     * @dataProvider afterRequestShippingRatesDataProvider
     *
     * @param string $code
     * @param float $price
     * @param bool $result
     * @param int $expectedResult
     * @param $call
     * @param string $shippingMethod
     * @param $deletedCall
     * @param $setPriceCall
     * @return void
     */
    public function testAfterRequestShippingRates(
        string $code,
        float $price,
        bool $result,
        bool $expectedResult,
        $call,
        string $shippingMethod,
        $deletedCall,
        $setPriceCall
    ) {
        /**
         * @var Address|MockObject $address
         */
        $address = $this->createMock(Address::class);
        $quote = $this->createMock(Quote::class);

        $negotiableQuote = $this->createMock(NegotiableQuote::class);
        $negotiableQuote->expects($call)->method('getShippingPrice')->willReturn($price);
        $negotiableQuote->expects($call)->method('getId')->willReturn(1);

        $quoteExtensionAttributes = $this
            ->getMockBuilder(CartExtensionInterface::class)
            ->setMethods(['getNegotiableQuote'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quoteExtensionAttributes->expects($call)->method('getNegotiableQuote')->willReturn($negotiableQuote);
        $quote->expects($call)->method('getExtensionAttributes')->willReturn($quoteExtensionAttributes);
        $address->expects($call)->method('getQuote')->willReturn($quote);
        $rate = $this->getMockBuilder(Rate::class)
            ->setMethods(['getCode', 'getPrice', 'setData', 'setPrice', 'isDeleted'])
            ->disableOriginalConstructor()
            ->getMock();

        $rate->expects($call)->method('getCode')->willReturn($code);
        $rate->expects($call)->method('getPrice')->willReturn($price);
        $rate->expects($call)->method('setData')->willReturnSelf();
        $rate->expects($setPriceCall)->method('setPrice')->with($price)->willReturnSelf();
        $rate->expects($deletedCall)->method('isDeleted')->with(true)->willReturnSelf();
        $address->expects($call)->method('getAllShippingRates')->willReturn([$rate]);
        $address->expects($call)->method('getShippingMethod')->willReturn($shippingMethod);
        $address->expects($call)->method('setShippingAmount')->willReturnSelf();
        $this->appState->expects($call)->method('getAreaCode')->willReturn(Area::AREA_FRONTEND);

        $this->assertEquals($expectedResult, $this->addressPlugin->afterRequestShippingRates($address, $result));
    }

    /**
     * Data provider for testAfterRequestShippingRates().
     *
     * @return array
     */
    public function afterRequestShippingRatesDataProvider()
    {
        return [
            [
                'code'           => 'default',
                'price'          => 1.5,
                'result'         => true,
                'expectedResult' => true,
                'call'           => $this->atLeastOnce(),
                'shippingMethod' => 'default',
                'deletedCall'    => $this->never(),
                'setPriceCall'   => $this->atLeastOnce(),
            ],
            [
                'code'           => 'default',
                'price'          => 0,
                'result'         => true,
                'expectedResult' => true,
                'call'           => $this->atLeastOnce(),
                'shippingMethod' => 'default',
                'deletedCall'    => $this->never(),
                'setPriceCall'   => $this->never(),
            ],
            [
                'code'           => 'custom',
                'price'          => 1.5,
                'result'         => true,
                'expectedResult' => true,
                'call'           => $this->atLeastOnce(),
                'shippingMethod' => 'default',
                'deletedCall'    => $this->atLeastOnce(),
                'setPriceCall'   => $this->never(),
            ],
            [
                'code'           => 'default',
                'price'          => 0.00,
                'result'         => true,
                'expectedResult' => true,
                'call'           => $this->atLeastOnce(),
                'shippingMethod' => 'custom',
                'deletedCall'    => $this->atLeastOnce(),
                'setPriceCall'   => $this->never(),
            ],
            [
                'code'           => 'custom',
                'price'          => 0.00,
                'result'         => false,
                'expectedResult' => false,
                'call'           => $this->never(),
                'shippingMethod' => 'default',
                'deletedCall'    => $this->never(),
                'setPriceCall'   => $this->never(),
            ],
            [
                'code'           => 'custom',
                'price'          => 1.5,
                'result'         => true,
                'expectedResult' => true,
                'call'           => $this->atLeastOnce(),
                'shippingMethod' => 'custom',
                'deletedCall'    => $this->never(),
                'setPriceCall'   => $this->atLeastOnce(),
            ],
            [
                'code'           => 'default',
                'price'          => 1.5,
                'result'         => true,
                'expectedResult' => true,
                'call'           => $this->atLeastOnce(),
                'shippingMethod' => 'custom',
                'deletedCall'    => $this->atLeastOnce(),
                'setPriceCall'   => $this->never(),
            ],
        ];
    }
}
