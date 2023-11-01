<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Plugin\Company\Model;

use Magento\Company\Api\Data\CompanyInterface;
use Magento\NegotiableQuote\Api\Data\CompanyQuoteConfigInterface;
use Magento\NegotiableQuote\Helper\Company;
use Magento\NegotiableQuote\Model\Plugin\Company\Model\DataProvider as SystemUnderTest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataProviderTest extends TestCase
{
    /**
     * @var SystemUnderTest
     */
    private $dataProvider;

    /**
     * @var Company|MockObject
     */
    private $companyHelper;

    /**
     * @var \Magento\Company\Model\Company\DataProvider|MockObject
     */
    private $companyDataProvider;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        $this->companyHelper = $this->createMock(Company::class);

        $this->companyDataProvider =
            $this->getMockBuilder(\Magento\Company\Model\Company\DataProvider::class)->addMethods(['save'])
                ->disableOriginalConstructor()
                ->getMock();

        $this->dataProvider = new SystemUnderTest(
            $this->companyHelper
        );
    }

    /**
     * Test for method aroundGetSettingsData.
     *
     * @return void
     */
    public function testAroundGetSettingsData()
    {
        $quoteConfig = $this->getMockForAbstractClass(CompanyQuoteConfigInterface::class);
        $this->companyHelper
            ->expects($this->any())->method('getQuoteConfig')
            ->willReturn($quoteConfig);

        $company = $this->getMockForAbstractClass(
            CompanyInterface::class,
            [],
            '',
            false
        );
        $proceed = function () use ($company) {
            return [$company];
        };
        $quoteConfig->expects($this->any())->method('getIsQuoteEnabled')->willReturn(true);

        $this->dataProvider->aroundGetSettingsData($this->companyDataProvider, $proceed, $company);
    }
}
