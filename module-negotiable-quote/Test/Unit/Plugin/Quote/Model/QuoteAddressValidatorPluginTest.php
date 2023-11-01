<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Plugin\Quote\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\NegotiableQuote\Plugin\Quote\Model\QuoteAddressValidatorPlugin;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\QuoteAddressValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QuoteAddressValidatorPluginTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var QuoteAddressValidatorPlugin|PHPUnitFrameworkMockObjectMockObject
     */
    private $quoteAddressValidatorPlugin;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var QuoteAddressValidator|MockObject
     */
    private $subject;

    /**
     * @var AddressInterface|MockObject
     */
    private $addressData;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->setMethods(['getById'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->subject = $this->getMockBuilder(QuoteAddressValidator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressData = $this->getMockBuilder(AddressInterface::class)
            ->setMethods(['getCustomerId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->quoteAddressValidatorPlugin = $this->objectManagerHelper->getObject(
            QuoteAddressValidatorPlugin::class,
            [
                'customerRepository' => $this->customerRepository
            ]
        );
    }

    /**
     * Test aroundValidate method.
     *
     * @return void
     */
    public function testAroundValidate()
    {
        $expected = false;

        $closure = function () use ($expected) {
            return $expected;
        };

        $customerId = 364;
        $this->addressData->expects($this->exactly(1))->method('getCustomerId')->willReturn($customerId);

        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerRepository->expects($this->exactly(1))->method('getById')->willReturn($customer);

        $methodCallResult = $this->quoteAddressValidatorPlugin
            ->aroundValidate($this->subject, $closure, $this->addressData);

        $this->assertEquals($expected, $methodCallResult);
    }

    /**
     * Test aroundValidate method with Exception.
     *
     * @return void
     */
    public function testAroundValidateWithException()
    {
        $expected = true;

        $closure = function () {
        };

        $phrase = new Phrase('message');
        $exception = new NoSuchEntityException($phrase);
        $this->customerRepository->expects($this->exactly(1))->method('getById')->willThrowException($exception);

        $methodCallResult = $this->quoteAddressValidatorPlugin
            ->aroundValidate($this->subject, $closure, $this->addressData);

        $this->assertEquals($expected, $methodCallResult);
    }
}
