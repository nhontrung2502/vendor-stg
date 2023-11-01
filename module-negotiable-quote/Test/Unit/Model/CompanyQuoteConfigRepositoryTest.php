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
use Magento\NegotiableQuote\Model\CompanyQuoteConfigRepository;
use Magento\NegotiableQuote\Model\ResourceModel\CompanyQuoteConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CompanyQuoteConfigRepositoryTest extends TestCase
{
    /**
     * @var CompanyQuoteConfigRepository
     */
    private $repository;

    /**
     * @var CompanyQuoteConfigInterfaceFactory|MockObject
     */
    private $companyQuoteConfigFactory;

    /**
     * @var CompanyQuoteConfig|MockObject
     */
    private $companyQuoteConfigResource;

    /**
     * @var CompanyQuoteConfigInterface|PHPUnitFrameworkMockObjectMockObject
     */
    private $companyQuoteConfig;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->companyQuoteConfigFactory = $this->createMock(
            CompanyQuoteConfigInterfaceFactory::class
        );
        $this->companyQuoteConfigResource = $this->createMock(
            CompanyQuoteConfig::class
        );
        $this->companyQuoteConfig = $this->createMock(
            \Magento\NegotiableQuote\Model\CompanyQuoteConfig::class
        );
        $objectManager = new ObjectManager($this);
        $this->repository = $objectManager->getObject(
            CompanyQuoteConfigRepository::class,
            [
                'companyQuoteConfigFactory' => $this->companyQuoteConfigFactory,
                'companyQuoteConfigResource' => $this->companyQuoteConfigResource
            ]
        );
    }

    /**
     * Test for method save
     */
    public function testSave()
    {
        $this->companyQuoteConfigResource->expects($this->once())
            ->method('save')->with($this->companyQuoteConfig)->willReturnSelf();
        $this->assertTrue($this->repository->save($this->companyQuoteConfig));
    }

    /**
     * Test for method save with CouldNotSaveException exception.
     *
     * @return void
     */
    public function testSaveWithCouldNotSaveException()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotSaveException');
        $this->expectExceptionMessage('There was an error saving company quote config.');
        $exception = new \Exception();
        $this->companyQuoteConfigResource->expects($this->once())
            ->method('save')->with($this->companyQuoteConfig)->willThrowException($exception);

        $this->repository->save($this->companyQuoteConfig);
    }
}
