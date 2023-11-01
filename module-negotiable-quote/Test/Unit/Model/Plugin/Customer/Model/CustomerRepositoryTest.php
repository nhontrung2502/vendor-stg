<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Plugin\Customer\Model;

use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Customer\Api\CustomerNameGenerationInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\Plugin\Customer\Model\CustomerRepository;
use Magento\NegotiableQuote\Model\Purged\Extractor;
use Magento\NegotiableQuote\Model\Purged\Handler;
use Magento\NegotiableQuote\Model\ResourceModel\QuoteGrid;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerRepositoryTest extends TestCase
{
    /**
     * @var QuoteGrid|MockObject
     */
    private $quoteGrid;

    /**
     * @var CustomerNameGenerationInterface|MockObject
     */
    private $customerViewHelper;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepositoryMock;

    /**
     * @var Extractor|MockObject
     */
    private $extractor;

    /**
     * @var Handler|MockObject
     */
    private $purgedContentsHandler;

    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * SetUp.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->quoteGrid = $this->createMock(QuoteGrid::class);
        $this->customerViewHelper =
            $this->createPartialMock(CustomerNameGenerationInterface::class, ['getCustomerName']);
        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->onlyMethods(['getById'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->extractor = $this->getMockBuilder(Extractor::class)
            ->onlyMethods(['extractCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->purgedContentsHandler = $this->getMockBuilder(Handler::class)
            ->onlyMethods(['process'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->customerRepository = $objectManager->getObject(
            CustomerRepository::class,
            [
                'quoteGrid' => $this->quoteGrid,
                'customerViewHelper' => $this->customerViewHelper,
                'customerRepository' => $this->customerRepositoryMock,
                'extractor' => $this->extractor,
                'purgedContentsHandler' => $this->purgedContentsHandler
            ]
        );
    }

    /**
     * Test around save.
     *
     * @return void
     */
    public function testAroundSave(): void
    {
        $this->customerViewHelper->method('getCustomerName')
            ->willReturnOnConsecutiveCalls('Name', 'New Name', 'New Name');
        $oldCustomer = $this->getMockForAbstractClass(CustomerInterface::class);
        $this->customerRepositoryMock->expects($this->any())->method('getById')->willReturn($oldCustomer);
        /**
         * @var CustomerInterface|MockObject $customer
         */
        $customer = $this->getMockForAbstractClass(CustomerInterface::class);
        $customer->expects($this->any())->method('getId')->willReturn(1);
        $closure = function () use ($customer) {
            return $customer;
        };

        $this->assertInstanceOf(
            CustomerInterface::class,
            $this->customerRepository->aroundSave($this->customerRepositoryMock, $closure, $customer)
        );
    }

    /**
     * Test beforeDeleteById method.
     *
     * @return void
     */
    public function testBeforeDeleteById(): void
    {
        $customerId = 1;

        $companyId = 23;
        $companyAttributes = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->onlyMethods(['getCompanyId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $companyAttributes->expects($this->exactly(1))->method('getCompanyId')->willReturn($companyId);

        $extensionAttributes = $this->getMockBuilder(CustomerExtensionInterface::class)
            ->addMethods(['getCompanyAttributes'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $extensionAttributes->expects($this->exactly(2))->method('getCompanyAttributes')
            ->willReturn($companyAttributes);

        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->onlyMethods(['getExtensionAttributes'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customer->expects($this->exactly(3))->method('getExtensionAttributes')->willReturn($extensionAttributes);

        $this->customerRepositoryMock->expects($this->exactly(2))->method('getById')
            ->with($customerId)->willReturn($customer);

        $associatedCustomerData = [];
        $this->extractor->expects($this->exactly(1))->method('extractCustomer')->willReturn($associatedCustomerData);

        $this->purgedContentsHandler->expects($this->exactly(1))->method('process');

        $this->customerRepository->beforeDeleteById($this->customerRepositoryMock, $customerId);
    }
}
