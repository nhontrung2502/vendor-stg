<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Validator;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\NegotiableQuote\Api\Data\CompanyQuoteConfigInterface;
use Magento\NegotiableQuote\Helper\Company;
use Magento\NegotiableQuote\Model\Validator\Customer;
use Magento\NegotiableQuote\Model\Validator\ValidatorResult;
use Magento\NegotiableQuote\Model\Validator\ValidatorResultFactory;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Customer.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerTest extends TestCase
{
    /**
     * @var CompanyManagementInterface|MockObject
     */
    private $companyManagement;

    /**
     * @var Company|MockObject
     */
    private $companyHelper;

    /**
     * @var ValidatorResultFactory|MockObject
     */
    private $validatorResultFactory;

    /**
     * @var Customer
     */
    private $customer;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->companyManagement = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyHelper = $this->getMockBuilder(Company::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validatorResultFactory = $this
            ->getMockBuilder(ValidatorResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->customer = $objectManagerHelper->getObject(
            Customer::class,
            [
                'companyManagement' => $this->companyManagement,
                'companyHelper' => $this->companyHelper,
                'validatorResultFactory' => $this->validatorResultFactory,
            ]
        );
    }

    /**
     * Test for validate().
     *
     * @return void
     */
    public function testValidate()
    {
        $companyId = 1;
        $customerId = 2;
        $result = $this->getMockBuilder(ValidatorResult::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validatorResultFactory->expects($this->atLeastOnce())->method('create')->willReturn($result);
        $companyAttributes = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $companyAttributes->expects($this->atLeastOnce())->method('getCompanyId')->willReturn($companyId);
        $extensionAttributes = $this->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyAttributes'])
            ->getMockForAbstractClass();
        $extensionAttributes->expects($this->atLeastOnce())->method('getCompanyAttributes')
            ->willReturn($companyAttributes);
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customer->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $customer->expects($this->atLeastOnce())->method('getId')->willReturn($customerId);
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quote->expects($this->atLeastOnce())->method('getCustomer')->willReturn($customer);
        $quote->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyManagement->expects($this->atLeastOnce())->method('getByCustomerId')->with($customerId)
            ->willReturn($company);
        $quoteConfig = $this->getMockBuilder(CompanyQuoteConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quoteConfig->expects($this->atLeastOnce())->method('getIsQuoteEnabled')->willReturn(false);
        $this->companyHelper->expects($this->atLeastOnce())->method('getQuoteConfig')->with($company)
            ->willReturn($quoteConfig);
        $result->expects($this->atLeastOnce())->method('addMessage')->willReturnSelf();

        $this->assertInstanceOf(
            ValidatorResult::class,
            $this->customer->validate(['quote' => $quote])
        );
    }

    /**
     * Test validate() with empty customer company attributes.
     *
     * @return void
     */
    public function testValidateWithEmptyExtensionAttributes()
    {
        $result = $this->getMockBuilder(ValidatorResult::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validatorResultFactory->expects($this->atLeastOnce())->method('create')->willReturn($result);
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quote->expects($this->atLeastOnce())->method('getCustomer')->willReturn($customer);
        $quote->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $customer->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn(null);
        $result->expects($this->atLeastOnce())->method('addMessage')->willReturnSelf();
        $this->companyManagement->expects($this->never())->method('getByCustomerId');

        $this->assertInstanceOf(
            ValidatorResult::class,
            $this->customer->validate(['quote' => $quote])
        );
    }

    /**
     * Test validate() with empty quote.
     *
     * @return void
     */
    public function testValidateWithEmptyQuote()
    {
        $result = $this->getMockBuilder(ValidatorResult::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validatorResultFactory->expects($this->atLeastOnce())->method('create')->willReturn($result);

        $this->assertInstanceOf(
            ValidatorResult::class,
            $this->customer->validate([])
        );
    }
}
