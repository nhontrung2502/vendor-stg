<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Helper;

use Magento\Company\Api\Data\CompanyExtensionFactory;
use Magento\Company\Api\Data\CompanyExtensionInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\CompanyQuoteConfigInterface;
use Magento\NegotiableQuote\Helper\Company;
use Magento\NegotiableQuote\Model\CompanyQuoteConfigManagement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for \Magento\NegotiableQuote\Helper\Company class.
 */
class CompanyTest extends TestCase
{
    /**
     * @var Company
     */
    private $helper;

    /**
     * @var CompanyExtensionFactory|MockObject
     */
    private $companyExtensionFactoryMock;

    /**
     * @var MockObject
     */
    private $quoteConfigManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $arguments = $objectManagerHelper->getConstructArguments(Company::class);
        $this->companyExtensionFactoryMock =
            $this->createPartialMock(CompanyExtensionFactory::class, ['create']);
        $this->quoteConfigManager = $this->createPartialMock(
            CompanyQuoteConfigManagement::class,
            ['getByCompanyId']
        );

        $arguments['quoteConfigManager'] = $this->quoteConfigManager;
        $arguments['companyExtensionFactory'] = $this->companyExtensionFactoryMock;

        $this->helper =
            $objectManagerHelper->getObject(Company::class, $arguments);
    }

    /**
     * Test for loadQuoteConfig.
     *
     * @return void
     */
    public function testLoadQuoteConfig(): void
    {
        $companyMock = $this->createPartialMock(
            \Magento\Company\Model\Company::class,
            ['setExtensionAttributes', 'getExtensionAttributes']
        );

        $companyExtensionMock = $this->getMockBuilder(CompanyExtensionInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getQuoteConfig', 'setQuoteConfig'])
            ->getMockForAbstractClass();

        $companyQuoteConfigMock = $this->getMockBuilder(CompanyQuoteConfigInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getQuoteConfig', 'setQuoteConfig'])
            ->getMockForAbstractClass();

        $companyExtensionMock->method('getQuoteConfig')
            ->willReturnOnConsecutiveCalls(null, $companyQuoteConfigMock);
        $this->quoteConfigManager
            ->expects($this->any())
            ->method('getByCompanyId')
            ->willReturn($companyQuoteConfigMock);

        $companyMock->method('getExtensionAttributes')
            ->willReturnOnConsecutiveCalls($companyExtensionMock, $companyExtensionMock);

        $company = $this->helper->loadQuoteConfig($companyMock);

        $this->assertInstanceOf(CompanyInterface::class, $company);
    }
}
