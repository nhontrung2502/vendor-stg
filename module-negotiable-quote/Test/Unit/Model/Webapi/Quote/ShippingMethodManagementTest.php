<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Webapi\Quote;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\Webapi\CustomerCartValidator;
use Magento\NegotiableQuote\Model\Webapi\Quote\ShippingMethodManagement;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Quote\Api\ShippingMethodManagementInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShippingMethodManagementTest extends TestCase
{
    /**
     * @var ShippingMethodManagementInterface|MockObject
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
     * @var int
     */
    private $addressId = 1;

    /**
     * @var ShippingMethodManagement|PHPUnitFrameworkMockObjectMockObject
     */
    private $shippingMethodManagement;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->originalInterface = $this->getMockForAbstractClass(ShippingMethodManagementInterface::class);
        $this->validator = $this->createMock(CustomerCartValidator::class);
        $objectManager = new ObjectManager($this);
        $this->shippingMethodManagement = $objectManager->getObject(
            ShippingMethodManagement::class,
            [
                'originalInterface' => $this->originalInterface,
                'validator' => $this->validator
            ]
        );
    }

    /**
     * Test estimateByAddressId
     */
    public function testEstimateByAddressId()
    {
        $this->validator->expects($this->any())->method('validate')->willReturn(null);
        /**
         * @var ShippingMethodInterface $shippingMethod
         */
        $shippingMethod = $this->getMockForAbstractClass(ShippingMethodInterface::class);
        $this->originalInterface->expects($this->any())->method('estimateByAddressId')->willReturn([$shippingMethod]);

        $this->assertEquals(
            [$shippingMethod],
            $this->shippingMethodManagement->estimateByAddressId($this->cartId, $this->addressId)
        );
    }
}
