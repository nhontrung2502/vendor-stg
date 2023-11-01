<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Webapi\Checkout;

use Magento\Checkout\Api\Data\PaymentDetailsInterface;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Api\ShippingInformationManagementInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface;
use Magento\NegotiableQuote\Model\NegotiableQuote;
use Magento\NegotiableQuote\Model\Webapi\Checkout\ShippingInformationManagement;
use Magento\NegotiableQuote\Model\Webapi\CustomerCartValidator;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Model\Quote;
use Magento\Tax\Helper\Data;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ShippingInformationManagementTest extends TestCase
{
    /**
     * @var ShippingInformationManagementInterface|MockObject
     */
    private $originalInterface;

    /**
     * @var CustomerCartValidator|MockObject
     */
    private $validator;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var Data|MockObject
     */
    private $taxHelper;

    /**
     * @var NegotiableQuoteItemManagementInterface|PHPUnitFrameworkMockObjectMockObject
     */
    private $quoteItemManagement;

    /**
     * @var int
     */
    private $cartId = 1;

    /**
     * @var ShippingInformationManagement|PHPUnitFrameworkMockObjectMockObject
     */
    private $shippingInformationManagement;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->originalInterface =
            $this->getMockForAbstractClass(ShippingInformationManagementInterface::class);
        $this->validator = $this->createMock(CustomerCartValidator::class);
        $this->quoteRepository = $this->getMockForAbstractClass(
            CartRepositoryInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['get']
        );
        $quote = $this->createPartialMock(
            Quote::class,
            [
                'getExtensionAttributes',
                'getId'
            ]
        );
        $quoteNegotiation = $this->createMock(NegotiableQuote::class);
        $quoteNegotiation->expects($this->any())->method('getIsRegularQuote')->willReturn(true);
        $quoteNegotiation
            ->expects($this->any())
            ->method('setIsAddressDraft')
            ->with(true)
            ->willReturn(true);
        $quoteNegotiation->expects($this->any())->method('getNegotiatedPriceValue')->willReturn(null);
        $extensionAttributes = $this->getMockForAbstractClass(
            CartExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getNegotiableQuote']
        );
        $extensionAttributes
            ->expects($this->any())
            ->method('getNegotiableQuote')
            ->willReturn($quoteNegotiation);
        $quote
            ->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        $quote
            ->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->quoteRepository->expects($this->any())->method('get')->willReturn($quote);
        $this->taxHelper = $this->createMock(Data::class);
        $this->taxHelper->expects($this->any())->method('getTaxBasedOn')->willReturn('shipping');
        $this->quoteItemManagement = $this->getMockForAbstractClass(
            NegotiableQuoteItemManagementInterface::class
        );
        $this->quoteItemManagement->expects($this->any())
            ->method('recalculateOriginalPriceTax')
            ->with(1, true, true)
            ->willReturn(true);
        $objectManager = new ObjectManager($this);
        $this->shippingInformationManagement = $objectManager->getObject(
            ShippingInformationManagement::class,
            [
                'originalInterface' => $this->originalInterface,
                'validator' => $this->validator,
                'quoteRepository' => $this->quoteRepository,
                'quoteItemManagement' => $this->quoteItemManagement,
                'taxHelper' => $this->taxHelper
            ]
        );
    }

    /**
     * Test saveAddressInformation
     */
    public function testSaveAddressInformation()
    {
        $this->validator->expects($this->any())->method('validate')->willReturn(null);
        /**
         * @var ShippingInformationInterface $addressInformation
         */
        $addressInformation = $this->getMockForAbstractClass(ShippingInformationInterface::class);
        $paymentDetails = $this->getMockForAbstractClass(PaymentDetailsInterface::class);
        $this->originalInterface->expects($this->any())->method('saveAddressInformation')
            ->willReturn($paymentDetails);

        $this->assertInstanceOf(
            PaymentDetailsInterface::class,
            $this->shippingInformationManagement->saveAddressInformation($this->cartId, $addressInformation)
        );
    }
}
