<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Webapi;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\Webapi\CustomerCartValidator;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerCartValidatorTest extends TestCase
{
    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var CustomerCartValidator|MockObject
     */
    private $customerCartValidator;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->userContext = $this->getMockForAbstractClass(UserContextInterface::class);
        $this->quoteRepository = $this->getMockForAbstractClass(CartRepositoryInterface::class);
        $objectManager = new ObjectManager($this);
        $this->customerCartValidator = $objectManager->getObject(
            CustomerCartValidator::class,
            [
                'userContext' => $this->userContext,
                'quoteRepository' => $this->quoteRepository
            ]
        );
    }

    /**
     * Test validate
     *
     * @param int $customerId
     * @param int $userType
     * @param int $quoteCustomerId
     * @dataProvider validateDataProvider
     */
    public function testValidate($customerId, $userType, $quoteCustomerId)
    {
        $this->prepareMockData($customerId, $userType, $quoteCustomerId);

        $this->assertNull($this->customerCartValidator->validate(1));
    }

    /**
     * Test validateWithException
     *
     * @param int $customerId
     * @param int $userType
     * @param int $quoteCustomerId
     * @dataProvider validateWithExceptionDataProvider
     */
    public function testValidateWithException($customerId, $userType, $quoteCustomerId)
    {
        $this->expectException('Magento\Framework\Exception\SecurityViolationException');
        $this->prepareMockData($customerId, $userType, $quoteCustomerId);

        $this->customerCartValidator->validate(1);
    }

    /**
     * validate dataProvider
     *
     * @return array
     */
    public function validateDataProvider()
    {
        return [
            [1, UserContextInterface::USER_TYPE_CUSTOMER, 1]
        ];
    }

    /**
     * testValidateWithException dataProvider
     *
     * @return array
     */
    public function validateWithExceptionDataProvider()
    {
        return [
            [1, UserContextInterface::USER_TYPE_CUSTOMER, 2],
            [1, UserContextInterface::USER_TYPE_GUEST, 1],
            [1, UserContextInterface::USER_TYPE_GUEST, 2]
        ];
    }

    /**
     * @param int $customerId
     * @param int $userType
     * @param int $quoteCustomerId
     */
    private function prepareMockData($customerId, $userType, $quoteCustomerId)
    {
        $this->userContext->expects($this->any())->method('getUserType')->willReturn($userType);
        $this->userContext->expects($this->any())->method('getUserId')->willReturn($customerId);
        $customer = $this->getMockForAbstractClass(CustomerInterface::class);
        $customer->expects($this->any())->method('getId')->willReturn($quoteCustomerId);
        $quote = $this->getMockForAbstractClass(CartInterface::class);
        $quote->expects($this->any())->method('getCustomer')->willReturn($customer);
        $this->quoteRepository->expects($this->any())->method('get')->willReturn($quote);
    }
}
