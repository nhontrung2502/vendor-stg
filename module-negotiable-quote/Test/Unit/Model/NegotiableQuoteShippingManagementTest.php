<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Model\NegotiableQuoteShippingManagement;
use Magento\NegotiableQuote\Model\Quote\History;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\NegotiableQuote\Model\Validator\ValidatorInterface;
use Magento\NegotiableQuote\Model\Validator\ValidatorInterfaceFactory;
use Magento\NegotiableQuote\Model\Validator\ValidatorResult;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Api\Data\ShippingInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Rate;
use Magento\Quote\Model\Quote\ShippingAssignment\ShippingAssignmentProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for negotiable quote shipping method set.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class NegotiableQuoteShippingManagementTest extends TestCase
{
    /**
     * @var NegotiableQuoteManagementInterface|MockObject
     */
    private $negotiableQuoteManagement;

    /**
     * @var ShippingAssignmentProcessor|MockObject
     */
    private $shippingAssignmentProcessor;

    /**
     * @var ValidatorInterfaceFactory|MockObject
     */
    private $validatorFactory;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var RestrictionInterface|MockObject
     */
    private $restriction;

    /**
     * @var History|MockObject
     */
    private $quoteHistory;

    /**
     * @var NegotiableQuoteShippingManagement
     */
    private $object;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->negotiableQuoteManagement = $this->getMockBuilder(
            NegotiableQuoteManagementInterface::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->shippingAssignmentProcessor = $this->getMockBuilder(
            ShippingAssignmentProcessor::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->validatorFactory = $this->getMockBuilder(
            ValidatorInterfaceFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->quoteRepository = $this->getMockBuilder(
            CartRepositoryInterface::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->restriction = $this->getMockBuilder(
            RestrictionInterface::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['setQuote'])
            ->getMockForAbstractClass();
        $this->quoteHistory = $this->getMockBuilder(History::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->restriction->expects($this->once())
            ->method('setQuote')
            ->willReturn(1);

        $objectManager = new ObjectManager($this);
        $this->object = $objectManager->getObject(
            NegotiableQuoteShippingManagement::class,
            [
                'negotiableQuoteManagement' => $this->negotiableQuoteManagement,
                'shippingAssignmentProcessor' => $this->shippingAssignmentProcessor,
                'validatorFactory' => $this->validatorFactory,
                'quoteRepository' => $this->quoteRepository,
                'restriction' => $this->restriction,
                'quoteHistory' => $this->quoteHistory,
            ]
        );
    }

    /**
     * Test setShippingMethod for negotiable quote.
     *
     * @return void
     */
    public function testSetShippingMethod()
    {
        $quoteId = 1;
        $shippingCode = 'flatrate_flatrate';

        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setStatus'])
            ->getMockForAbstractClass();
        $negotiableQuote->expects($this->once())
            ->method('setStatus')
            ->with(NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN)
            ->willReturn(true);
        $shippingRate = $this->getMockBuilder(Rate::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCode'])
            ->getMock();
        $shippingRate->expects($this->once())->method('getCode')->willReturn($shippingCode);
        $address = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCountryId', 'getShippingRateByCode', 'setCollectShippingRates', 'collectShippingRates'])
            ->getMockForAbstractClass();
        $address->expects($this->once())->method('getCountryId')->willReturn('SU');
        $address->expects($this->once())->method('getShippingRateByCode')->willReturn($shippingRate);
        $shipping = $this->getMockBuilder(ShippingInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAddress', 'getMethod', 'setMethod'])
            ->getMockForAbstractClass();
        $shipping->expects($this->atLeastOnce())->method('getAddress')->willReturn($address);
        $shippingAssignments = $this->getMockBuilder(ShippingAssignmentInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShipping'])
            ->getMockForAbstractClass();
        $shippingAssignments->expects($this->atLeastOnce())->method('getShipping')->willReturn($shipping);
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShippingAssignments', 'getNegotiableQuote'])
            ->getMockForAbstractClass();
        $extensionAttributes->expects($this->atLeastOnce())
            ->method('getShippingAssignments')
            ->willReturn([$shippingAssignments]);
        $extensionAttributes->expects($this->atLeastOnce())->method('getNegotiableQuote')->willReturn($negotiableQuote);
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIsVirtual', 'getExtensionAttributes'])
            ->getMock();
        $quote->expects($this->once())->method('getIsVirtual')->willReturn(false);
        $quote->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $this->negotiableQuoteManagement->expects($this->once())
            ->method('getNegotiableQuote')
            ->with($quoteId)
            ->willReturn($quote);
        $this->prepareCorrectValidator($quote);

        $result = $this->object->setShippingMethod($quoteId, $shippingCode);
        $this->assertTrue($result);
    }

    /**
     * Test setShippingMethod for negotiable quote with quote validate exception.
     *
     * @return void
     */
    public function testSetShippingMethodWithQuoteValidateException()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage('Cannot obtain the requested data. You must fix the errors listed below first.');

        $quoteId = 1;
        $shippingCode = 'flatrate_flatrate';

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIsVirtual', 'getExtensionAttributes'])
            ->getMock();
        $this->negotiableQuoteManagement->expects($this->once())
            ->method('getNegotiableQuote')
            ->with($quoteId)
            ->willReturn($quote);

        $validatorResult = $this->getMockBuilder(ValidatorResult::class)
            ->disableOriginalConstructor()
            ->setMethods(['hasMessages', 'getMessages'])
            ->getMock();
        $validatorResult->expects($this->once())
            ->method('hasMessages')
            ->willReturn(true);
        $validatorResult->expects($this->once())
            ->method('getMessages')
            ->willReturn([__('Message 1'), __('Message 2')]);
        $validator = $this->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['validate'])
            ->getMockForAbstractClass();
        $validator->expects($this->once())
            ->method('validate')
            ->with(['quote' => $quote])
            ->willReturn($validatorResult);
        $this->validatorFactory->expects($this->once())
            ->method('create')
            ->with(['action' => 'edit'])
            ->willReturn($validator);

        $result = $this->object->setShippingMethod($quoteId, $shippingCode);
        $this->assertTrue($result);
    }

    /**
     * Test setShippingMethod for negotiable quote with quote is not active exception.
     *
     * @return void
     */
    public function testSetShippingMethodWithQuoteIsNotActiveException()
    {
        $this->expectException('Magento\Framework\Exception\StateException');
        $this->expectExceptionMessage('Shipping method cannot be set for a virtual quote.');
        $quoteId = 1;
        $shippingCode = 'flatrate_flatrate';

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIsVirtual', 'getExtensionAttributes'])
            ->getMock();
        $quote->expects($this->once())->method('getIsVirtual')->willReturn(true);
        $this->negotiableQuoteManagement->expects($this->once())
            ->method('getNegotiableQuote')
            ->with($quoteId)
            ->willReturn($quote);
        $this->prepareCorrectValidator($quote);

        $result = $this->object->setShippingMethod($quoteId, $shippingCode);
        $this->assertTrue($result);
    }

    /**
     * Test setShippingMethod for negotiable quote with no shipping address exception.
     *
     * @return void
     */
    public function testSetShippingMethodWithNoShippingAddressException()
    {
        $this->expectException('Magento\Framework\Exception\StateException');
        $this->expectExceptionMessage(
            'Cannot add the shipping method. You must add a shipping address into the quote first.'
        );
        $quoteId = 1;
        $shippingCode = 'flatrate_flatrate';

        $shippingAssignments = $this->getMockBuilder(ShippingAssignmentInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShipping'])
            ->getMockForAbstractClass();
        $shippingAssignments->expects($this->atLeastOnce())->method('getShipping')->willReturn(null);
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShippingAssignments', 'getNegotiableQuote'])
            ->getMockForAbstractClass();
        $extensionAttributes->expects($this->atLeastOnce())
            ->method('getShippingAssignments')
            ->willReturn([$shippingAssignments]);
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIsVirtual', 'getExtensionAttributes'])
            ->getMock();
        $quote->expects($this->once())->method('getIsVirtual')->willReturn(false);
        $quote->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $this->negotiableQuoteManagement->expects($this->once())
            ->method('getNegotiableQuote')
            ->with($quoteId)
            ->willReturn($quote);
        $this->prepareCorrectValidator($quote);

        $result = $this->object->setShippingMethod($quoteId, $shippingCode);
        $this->assertTrue($result);
    }

    /**
     * Test setShippingMethod for negotiable quote with no shipping rate exception.
     *
     * @return void
     */
    public function testSetShippingMethodWithNoShippingRateException()
    {
        $this->expectException('Magento\Framework\Exception\NotFoundException');
        $this->expectExceptionMessage(
            'Requested shipping method is not found. Row ID: ShippingMethodID = flatrate_flatrate.'
        );
        $quoteId = 1;
        $shippingCode = 'flatrate_flatrate';
        $shippingRate = null;
        $address = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCountryId', 'getShippingRateByCode', 'setCollectShippingRates', 'collectShippingRates'])
            ->getMockForAbstractClass();
        $address->expects($this->once())->method('getCountryId')->willReturn('SU');
        $address->expects($this->once())->method('getShippingRateByCode')->willReturn($shippingRate);
        $shipping = $this->getMockBuilder(ShippingInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAddress', 'getMethod', 'setMethod'])
            ->getMockForAbstractClass();
        $shipping->expects($this->atLeastOnce())->method('getAddress')->willReturn($address);
        $shippingAssignments = $this->getMockBuilder(ShippingAssignmentInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShipping'])
            ->getMockForAbstractClass();
        $shippingAssignments->expects($this->atLeastOnce())->method('getShipping')->willReturn($shipping);
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShippingAssignments', 'getNegotiableQuote'])
            ->getMockForAbstractClass();
        $extensionAttributes->expects($this->atLeastOnce())
            ->method('getShippingAssignments')
            ->willReturn([$shippingAssignments]);
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIsVirtual', 'getExtensionAttributes'])
            ->getMock();
        $quote->expects($this->once())->method('getIsVirtual')->willReturn(false);
        $quote->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $this->negotiableQuoteManagement->expects($this->once())
            ->method('getNegotiableQuote')
            ->with($quoteId)
            ->willReturn($quote);
        $this->prepareCorrectValidator($quote);

        $result = $this->object->setShippingMethod($quoteId, $shippingCode);
        $this->assertTrue($result);
    }

    /**
     * Prepate quote validator object that will return no errors.
     *
     * @param CartInterface $quote
     * @return void
     */
    private function prepareCorrectValidator(CartInterface $quote)
    {
        $validatorResult = $this->getMockBuilder(ValidatorResult::class)
            ->disableOriginalConstructor()
            ->setMethods(['hasMessages', 'getMessages'])
            ->getMock();
        $validatorResult->expects($this->once())
            ->method('hasMessages')
            ->willReturn([]);
        $validator = $this->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['validate'])
            ->getMockForAbstractClass();
        $validator->expects($this->once())
            ->method('validate')
            ->with(['quote' => $quote])
            ->willReturn($validatorResult);
        $this->validatorFactory->expects($this->once())
            ->method('create')
            ->with(['action' => 'edit'])
            ->willReturn($validator);
    }
}
