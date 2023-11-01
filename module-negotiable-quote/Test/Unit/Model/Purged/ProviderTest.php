<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Purged;

use Magento\Company\Api\Data\CompanyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\NegotiableQuote\Model\Purged\Provider;
use Magento\NegotiableQuote\Model\PurgedContent;
use Magento\NegotiableQuote\Model\PurgedContentFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProviderTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var Provider|MockObject
     */
    private $provider;

    /**
     * @var PurgedContentFactory|MockObject
     */
    private $purgedContentFactory;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->purgedContentFactory = $this->getMockBuilder(PurgedContentFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->provider = $this->objectManagerHelper->getObject(
            Provider::class,
            [
                'purgedContentFactory' => $this->purgedContentFactory
            ]
        );
    }

    /**
     * Set up Purged Data Mock.
     *
     * @param string $purgedDataJson
     * @return void
     */
    private function setUpPurgedDataMock($purgedDataJson)
    {
        $purgedContent = $this->getMockBuilder(PurgedContent::class)
            ->setMethods([
                'load',
                'getPurgedData'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $purgedContent->expects($this->exactly(1))->method('load')->willReturnSelf();
        $purgedContent->expects($this->exactly(1))->method('getPurgedData')->willReturn($purgedDataJson);

        $this->purgedContentFactory->expects($this->exactly(1))->method('create')->willReturn($purgedContent);
    }

    /**
     * Test getCustomerName method.
     *
     * @return void
     */
    public function testGetCustomerName()
    {
        $quoteId = 23;
        $expected = 'Customer Name';
        $purgedData = sprintf('{"customer_name": "%s"}', $expected);
        $this->setUpPurgedDataMock($purgedData);

        $this->assertEquals($expected, $this->provider->getCustomerName($quoteId));
    }

    /**
     * Test getCompanyId method.
     *
     * @return void
     */
    public function testGetCompanyId()
    {
        $quoteId = 23;
        $expected = 89;
        $purgedData = sprintf('{"%s": "%s"}', CompanyInterface::COMPANY_ID, $expected);
        $this->setUpPurgedDataMock($purgedData);

        $this->assertEquals($expected, $this->provider->getCompanyId($quoteId));
    }

    /**
     * Test getCompanyName method.
     *
     * @return void
     */
    public function testGetCompanyName()
    {
        $quoteId = 23;
        $expected = 'Company Name';
        $purgedData = sprintf('{"%s": "%s"}', CompanyInterface::NAME, $expected);
        $this->setUpPurgedDataMock($purgedData);

        $this->assertEquals($expected, $this->provider->getCompanyName($quoteId));
    }

    /**
     * Test getCompanyEmail method.
     *
     * @return void
     */
    public function testGetCompanyEmail()
    {
        $quoteId = 23;
        $expected = 'company@test.com';
        $purgedData = sprintf('{"%s": "%s"}', CompanyInterface::EMAIL, $expected);
        $this->setUpPurgedDataMock($purgedData);

        $this->assertEquals($expected, $this->provider->getCompanyEmail($quoteId));
    }

    /**
     * Test getSalesRepresentativeId method.
     *
     * @return void
     */
    public function testGetSalesRepresentativeId()
    {
        $quoteId = 23;
        $expected = 99;
        $dataKey = CompanyInterface::SALES_REPRESENTATIVE_ID;
        $purgedData = sprintf('{"%s": "%s"}', $dataKey, $expected);
        $this->setUpPurgedDataMock($purgedData);

        $this->assertEquals($expected, $this->provider->getSalesRepresentativeId($quoteId));
    }

    /**
     * Test getSalesRepresentativeName method.
     *
     * @return void
     */
    public function testGetSalesRepresentativeName()
    {
        $quoteId = 23;
        $expected = 'Customer Name';
        $purgedData = sprintf('{"sales_representative_name": "%s"}', $expected);
        $this->setUpPurgedDataMock($purgedData);

        $this->assertEquals($expected, $this->provider->getSalesRepresentativeName($quoteId));
    }
}
