<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Webapi\Quote;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\Webapi\CustomerCartValidator;
use Magento\NegotiableQuote\Model\Webapi\Quote\ShipmentEstimation;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Quote\Api\ShipmentEstimationInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShipmentEstimationTest extends TestCase
{
    /**
     * @var ShipmentEstimationInterface|MockObject
     */
    private $originalInterface;

    /**
     * @var CustomerCartValidator|MockObject
     */
    private $validator;

    /**
     * @var int
     */
    private $cartId = 1;

    /**
     * @var ShipmentEstimation|MockObject
     */
    private $shipmentEstimation;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->originalInterface = $this->getMockForAbstractClass(ShipmentEstimationInterface::class);
        $this->validator = $this->createMock(CustomerCartValidator::class);
        $objectManager = new ObjectManager($this);
        $this->shipmentEstimation = $objectManager->getObject(
            ShipmentEstimation::class,
            [
                'originalInterface' => $this->originalInterface,
                'validator' => $this->validator
            ]
        );
    }

    /**
     * Test estimateByExtendedAddress
     */
    public function testEstimateByExtendedAddress()
    {
        $this->validator->expects($this->any())->method('validate')->willReturn(null);
        /**
         * @var AddressInterface $address
         */
        $address = $this->getMockForAbstractClass(AddressInterface::class);
        /**
         * @var ShippingMethodInterface $shippingMethod
         */
        $shippingMethod = $this->getMockForAbstractClass(ShippingMethodInterface::class);
        $this->originalInterface->expects($this->any())->method('estimateByExtendedAddress')
            ->willReturn([$shippingMethod]);

        $this->assertEquals(
            [$shippingMethod],
            $this->shipmentEstimation->estimateByExtendedAddress($this->cartId, $address)
        );
    }
}
