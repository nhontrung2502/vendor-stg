<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Plugin\Company\Api;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\ResourceModel\Customer;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\NegotiableQuote\Api\CompanyQuoteConfigRepositoryInterface;
use Magento\NegotiableQuote\Api\Data\CompanyQuoteConfigInterface;
use Magento\NegotiableQuote\Helper\Company;
use Magento\NegotiableQuote\Model\Purged\Extractor;
use Magento\NegotiableQuote\Model\Purged\Handler;
use Magento\NegotiableQuote\Model\ResourceModel\QuoteGrid;
use Magento\NegotiableQuote\Plugin\Company\Api\CompanyRepositoryInterfacePlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CompanyRepositoryInterfacePluginTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var CompanyRepositoryInterfacePlugin|PHPUnitFrameworkMockObjectMockObject
     */
    private $companyRepositoryInterfacePlugin;

    /**
     * @var Extractor|MockObject
     */
    private $extractor;

    /**
     * @var Customer|MockObject
     */
    private $customerResource;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var CompanyQuoteConfigRepositoryInterface|MockObject
     */
    private $companyQuoteConfigRepository;

    /**
     * @var \Magento\NegotiableQuote\Helper\Company|MockObject
     */
    private $companyHelper;

    /**
     * @var QuoteGrid|MockObject
     */
    private $quoteGrid;

    /**
     * @var Handler|MockObject
     */
    private $purgedContentsHandler;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $subject;

    /**
     * @var CompanyInterface|MockObject
     */
    private $company;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->extractor = $this->getMockBuilder(Extractor::class)
            ->setMethods(['extractCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerResource = $this->getMockBuilder(Customer::class)
            ->setMethods(['getCustomerIdsByCompanyId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->setMethods(['getById'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyQuoteConfigRepository = $this
            ->getMockBuilder(CompanyQuoteConfigRepositoryInterface::class)
            ->setMethods(['save'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyHelper = $this->getMockBuilder(Company::class)
            ->setMethods(['getQuoteConfig'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteGrid = $this->getMockBuilder(QuoteGrid::class)
            ->setMethods(['refreshValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->purgedContentsHandler = $this->getMockBuilder(Handler::class)
            ->setMethods(['process'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->subject = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->company = $this->getMockBuilder(CompanyInterface::class)
            ->setMethods([
                'getId',
                'dataHasChangedFor',
                'getCompanyName'
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->companyRepositoryInterfacePlugin = $this->objectManagerHelper->getObject(
            CompanyRepositoryInterfacePlugin::class,
            [
                'extractor' => $this->extractor,
                'customerResource' => $this->customerResource,
                'customerRepository' => $this->customerRepository,
                'companyQuoteConfigRepository' => $this->companyQuoteConfigRepository,
                'companyHelper' => $this->companyHelper,
                'quoteGrid' => $this->quoteGrid,
                'purgedContentsHandler' => $this->purgedContentsHandler
            ]
        );
    }

    /**
     * Test aroundSave method.
     *
     * @return void
     */
    public function testAroundSave()
    {
        $closure = function () {
        };

        $companyId = 34;
        $companyName = 'Test Company';
        $this->company->expects($this->exactly(3))->method('getId')->willReturn($companyId);
        $this->company->expects($this->exactly(1))->method('dataHasChangedFor')->willReturn(true);
        $this->company->expects($this->exactly(1))->method('getCompanyName')->willReturn($companyName);

        $this->quoteGrid->expects($this->exactly(1))->method('refreshValue')->willReturnSelf();

        $quoteConfig = $this->getMockBuilder(CompanyQuoteConfigInterface::class)
            ->setMethods(['setCompanyId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quoteConfig->expects($this->exactly(1))->method('setCompanyId')->willReturnSelf();

        $this->companyHelper->expects($this->exactly(1))->method('getQuoteConfig')->willReturn($quoteConfig);

        $this->companyQuoteConfigRepository->expects($this->exactly(1))->method('save')->willReturn(true);

        $this->companyRepositoryInterfacePlugin->aroundSave($this->subject, $closure, $this->company);
    }

    /**
     * Test beforeDelete method.
     *
     * @return void
     */
    public function testBeforeDelete()
    {
        $companyId = 34;
        $this->company->expects($this->exactly(1))->method('getId')->willReturn($companyId);

        $customers = [2 => 2];
        $this->customerResource->expects($this->exactly(1))
            ->method('getCustomerIdsByCompanyId')->willReturn($customers);

        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerRepository->expects($this->exactly(1))->method('getById')->willReturn($customer);

        $customerData = [1, 2, 3];
        $this->extractor->expects($this->exactly(1))->method('extractCustomer')->willReturn($customerData);

        $this->purgedContentsHandler->expects($this->exactly(1))->method('process');

        $this->companyRepositoryInterfacePlugin->beforeDelete($this->subject, $this->company);
    }
}
