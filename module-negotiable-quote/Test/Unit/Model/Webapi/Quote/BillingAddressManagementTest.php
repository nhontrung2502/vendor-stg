<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Webapi\Quote;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface;
use Magento\NegotiableQuote\Model\NegotiableQuote;
use Magento\NegotiableQuote\Model\Webapi\CustomerCartValidator;
use Magento\NegotiableQuote\Model\Webapi\Quote\BillingAddressManagement;
use Magento\Quote\Api\BillingAddressManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Model\Quote;
use Magento\Tax\Helper\Data;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BillingAddressManagementTest extends TestCase
{
    /**
     * @var BillingAddressManagementInterface|MockObject
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
     * @var int
     */
    private $addressId = 1;

    /**
     * @var BillingAddressManagement|PHPUnitFrameworkMockObjectMockObject
     */
    private $billingAddressManagement;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->originalInterface = $this->getMockForAbstractClass(BillingAddressManagementInterface::class);
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
                'getId',
                'getIsVirtual'
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
        $quote
            ->expects($this->any())
            ->method('getIsVirtual')
            ->willReturn(true);
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
        $this->billingAddressManagement = $objectManager->getObject(
            BillingAddressManagement::class,
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
     * Test assign.
     *
     * @return void
     */
    public function testAssign()
    {
        $this->validator->expects($this->any())->method('validate')->willReturn(null);
        /**
         * @var AddressInterface $address
         */
        $address = $this->getMockForAbstractClass(AddressInterface::class);
        $this->originalInterface->expects($this->any())->method('assign')->willReturn($this->addressId);

        $this->assertEquals($this->addressId, $this->billingAddressManagement->assign($this->cartId, $address, false));
    }

    /**
     * Test get.
     *
     * @return void
     */
    public function testGet()
    {
        $this->validator->expects($this->any())->method('validate')->willReturn(null);
        /**
         * @var AddressInterface $address
         */
        $address = $this->getMockForAbstractClass(AddressInterface::class);
        $this->originalInterface->expects($this->any())->method('get')->willReturn($address);

        $this->assertEquals($address, $this->billingAddressManagement->get($this->cartId));
    }
}
