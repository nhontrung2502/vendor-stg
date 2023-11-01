<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Webapi\Checkout;

use Magento\Checkout\Api\Data\PaymentDetailsInterface;
use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\Webapi\Checkout\PaymentInformationManagement;
use Magento\NegotiableQuote\Model\Webapi\CustomerCartValidator;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentInformationManagementTest extends TestCase
{
    /**
     * @var PaymentInformationManagementInterface|MockObject
     */
    private $originalInterface;

    /**
     * @var CustomerCartValidator|MockObject
     */
    private $validator;

    /**
     * @var PaymentInformationManagement|PHPUnitFrameworkMockObjectMockObject
     */
    private $paymentInformationManagement;

    /**
     * @var int
     */
    private $cartId = 1;

    /**
     * @var int
     */
    private $orderId = 1;

    /**
     * @var PaymentInterface|MockObject
     */
    private $paymentMethod;

    /**
     * @var AddressInterface|MockObject
     */
    private $billingAddress;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->originalInterface =
            $this->getMockForAbstractClass(PaymentInformationManagementInterface::class);
        $this->validator = $this->createMock(CustomerCartValidator::class);
        $this->paymentMethod = $this->getMockForAbstractClass(PaymentInterface::class);
        $this->billingAddress = $this->getMockForAbstractClass(AddressInterface::class);
        $objectManager = new ObjectManager($this);
        $this->paymentInformationManagement = $objectManager->getObject(
            PaymentInformationManagement::class,
            [
                'originalInterface' => $this->originalInterface,
                'validator' => $this->validator
            ]
        );
    }

    /**
     * Test savePaymentInformationAndPlaceOrder
     */
    public function testSavePaymentInformationAndPlaceOrder()
    {
        $this->validator->expects($this->any())->method('validate')->willReturn(null);
        $this->originalInterface->expects($this->any())->method('savePaymentInformationAndPlaceOrder')
            ->willReturn($this->orderId);

        $this->assertEquals(
            $this->orderId,
            $this->paymentInformationManagement->savePaymentInformationAndPlaceOrder(
                $this->cartId,
                $this->paymentMethod,
                $this->billingAddress
            )
        );
    }

    /**
     * Test savePaymentInformation
     */
    public function testSavePaymentInformation()
    {
        $this->validator->expects($this->any())->method('validate')->willReturn(null);
        $this->originalInterface->expects($this->any())->method('savePaymentInformation')->willReturn(true);

        $this->assertTrue(
            $this->paymentInformationManagement->savePaymentInformation(
                $this->cartId,
                $this->paymentMethod,
                $this->billingAddress
            )
        );
    }

    /**
     * Test getPaymentInformation
     */
    public function testGetPaymentInformation()
    {
        $this->validator->expects($this->any())->method('validate')->willReturn(null);
        $paymentInformation = $this->getMockForAbstractClass(PaymentDetailsInterface::class);
        $this->originalInterface->expects($this->any())->method('getPaymentInformation')
            ->willReturn($paymentInformation);

        $this->assertInstanceOf(
            PaymentDetailsInterface::class,
            $this->paymentInformationManagement->getPaymentInformation($this->cartId)
        );
    }
}
