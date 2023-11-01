<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Purged;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Api\CustomerNameGenerationInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\NegotiableQuote\Model\Purged\Extractor;
use Magento\User\Api\Data\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Extractor model.
 */
class ExtractorTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var Extractor|MockObject
     */
    private $extractor;

    /**
     * @var CustomerNameGenerationInterface|MockObject
     */
    private $customerNameGenerator;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

    /**
     * @var CompanyManagementInterface|MockObject
     */
    private $companyManagement;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->customerNameGenerator = $this
            ->getMockBuilder(CustomerNameGenerationInterface::class)
            ->setMethods(['getCustomerName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyManagement = $this->getMockBuilder(CompanyManagementInterface::class)
            ->setMethods(['getSalesRepresentative', 'getAdminByCompanyId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->extractor = $this->objectManagerHelper->getObject(
            Extractor::class,
            [
                'customerNameGenerator' => $this->customerNameGenerator,
                'companyRepository' => $this->companyRepository,
                'companyManagement' => $this->companyManagement
            ]
        );
    }

    /**
     * Test extractCustomer method.
     *
     * @param int|null $salesRepresentativeId
     * @param string|null $salesRepName
     * @param array $calls
     * @dataProvider extractCustomerDataProvider
     * @return void
     */
    public function testExtractCustomer($salesRepresentativeId, $salesRepName, array $calls)
    {
        $customerName = 'Test Customer';
        $this->customerNameGenerator->expects($this->once())->method('getCustomerName')->willReturn($customerName);
        $companyId = 23;
        $groupId = 4;
        $companyAttributes = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $companyAttributes->expects($this->atLeastOnce())->method('getCompanyId')->willReturn($companyId);
        $extensionAttributes = $this->getMockBuilder(CustomerExtensionInterface::class)
            ->setMethods(['getCompanyAttributes'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $extensionAttributes->expects($this->atLeastOnce())
            ->method('getCompanyAttributes')
            ->willReturn($companyAttributes);
        $user = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $user->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $companyName = 'Test Company';
        $companyEmail = 'test_company@test.com';
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $company->expects($this->once())->method('getCompanyName')->willReturn($companyName);
        $company->expects($this->exactly($calls['getSalesRepresentativeId']))
            ->method('getSalesRepresentativeId')
            ->willReturn($salesRepresentativeId);
        $this->companyRepository->expects($this->once())->method('get')->willReturn($company);
        $this->companyManagement->expects($this->exactly($calls['getSalesRepresentative']))
            ->method('getSalesRepresentative')
            ->willReturn($salesRepName);
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->setMethods(['getEmail'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyManagement->expects($this->once())
            ->method('getAdminByCompanyId')
            ->willReturn($customer);
        $customer->expects($this->once())
            ->method('getEmail')
            ->willReturn($companyEmail);
        $customer->expects($this->once())
            ->method('getGroupId')
            ->willReturn($groupId);
        $data = [
            'customer_name' => $customerName,
            CompanyInterface::COMPANY_ID => $companyId,
            CompanyInterface::NAME => $companyName,
            CompanyInterface::EMAIL => $companyEmail,
            CompanyInterface::SALES_REPRESENTATIVE_ID => $salesRepresentativeId,
            CompanyInterface::CUSTOMER_GROUP_ID => $groupId
        ];
        if ($salesRepName) {
            $data['sales_representative_name'] = $salesRepName;
        }
        $this->assertEquals($data, $this->extractor->extractCustomer($user));
    }

    /**
     * Data provider for extractCustomer method.
     *
     * @return array
     */
    public function extractCustomerDataProvider()
    {
        return [
            [
                23, 'Sales Rep',
                [
                    'getSalesRepresentativeId' => 3,
                    'getSalesRepresentative' => 1,
                ]
            ],
            [
                null, null,
                [
                    'getSalesRepresentativeId' => 2,
                    'getSalesRepresentative' => 0
                ]
            ]
        ];
    }

    /**
     * Test extractUser method.
     *
     * @return void
     */
    public function testExtractUser()
    {
        $userId = 34;
        $userFirstName = 'Test';
        $userLastName = 'User';
        $user = $this->getUserMock();
        $user->expects($this->never())->method('getId')->willReturn($userId);
        $user->expects($this->never())->method('load')->willReturnSelf();
        $user->expects($this->exactly(2))->method('getFirstName')->willReturn($userFirstName);
        $user->expects($this->once())->method('getLastName')->willReturn($userLastName);

        $data = [
            'sales_representative_name' => $userFirstName . ' ' . $userLastName
        ];
        $this->assertEquals($data, $this->extractor->extractUser($user));
    }

    /**
     * Test extractUser method with load user.
     *
     * @return void
     */
    public function testExtractUserWithLoadUser()
    {
        $userId = 34;
        $userFirstName = 'Test';
        $userLastName = 'User';
        $user = $this->getUserMock();
        $user->expects($this->exactly(2))->method('getFirstName')->willReturnOnConsecutiveCalls(null, $userFirstName);
        $user->expects($this->once())->method('getId')->willReturn($userId);
        $user->expects($this->once())->method('load')->willReturnSelf();
        $user->expects($this->once())->method('getLastName')->willReturn($userLastName);

        $data = [
            'sales_representative_name' => $userFirstName . ' ' . $userLastName
        ];
        $this->assertEquals($data, $this->extractor->extractUser($user));
    }

    /**
     * Get Mock of the User class.
     *
     * @return MockObject
     */
    private function getUserMock(): MockObject
    {
        $user = $this->getMockBuilder(UserInterface::class)
            ->setMethods(
                [
                    'getId',
                    'load',
                    'getFirstName',
                    'getLastName',
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        return $user;
    }
}
