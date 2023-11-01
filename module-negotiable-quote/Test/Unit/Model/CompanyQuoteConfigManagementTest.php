<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\CompanyQuoteConfigInterface;
use Magento\NegotiableQuote\Api\Data\CompanyQuoteConfigInterfaceFactory;
use Magento\NegotiableQuote\Model\CompanyQuoteConfigManagement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for \Magento\NegotiableQuote\Model\CompanyQuoteConfigManagement.
 */
class CompanyQuoteConfigManagementTest extends TestCase
{
    /**
     * @var CompanyQuoteConfigManagement
     */
    private $companyQuoteConfigManagement;

    /**
     * @var CompanyQuoteConfigInterfaceFactory|MockObject
     */
    private $companyQuoteConfigFactory;

    /**
     * @var CompanyQuoteConfigInterface|MockObject
     */
    private $companyQuoteConfig;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->companyQuoteConfigFactory =
            $this->createPartialMock(CompanyQuoteConfigInterfaceFactory::class, ['create']);
        $this->companyQuoteConfig = $this->getMockForAbstractClass(
            CompanyQuoteConfigInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['load']
        );
        $this->companyQuoteConfigFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->companyQuoteConfig);

        $objectManager = new ObjectManager($this);
        $this->companyQuoteConfigManagement = $objectManager->getObject(
            CompanyQuoteConfigManagement::class,
            [
                'companyQuoteConfigFactory' => $this->companyQuoteConfigFactory,
            ]
        );
    }

    /**
     * Test for method getByCompanyId.
     *
     * @return void
     */
    public function testGetByCompanyId()
    {
        $companyId = 42;
        $this->companyQuoteConfig->expects($this->once())->method('load')->with($companyId)->willReturnSelf();

        $this->assertEquals($this->companyQuoteConfig, $this->companyQuoteConfigManagement->getByCompanyId($companyId));
    }
}
