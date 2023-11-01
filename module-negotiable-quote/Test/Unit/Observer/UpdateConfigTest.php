<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Observer;

use Magento\Company\Api\StatusServiceInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\Config;
use Magento\NegotiableQuote\Observer\UpdateConfig;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateConfigTest extends TestCase
{
    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManager;

    /**
     * @var StatusServiceInterface|MockObject
     */
    protected $companyStatusService;

    /**
     * @var Config|MockObject
     */
    protected $negotiableQuoteModuleConfig;

    /**
     * @var Observer|MockObject
     */
    protected $observer;

    /**
     * @var Event|MockObject
     */
    protected $event;

    /**
     * @var UpdateConfig|MockObject
     */
    protected $updateConfig;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->storeManager = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->companyStatusService =
            $this->getMockForAbstractClass(StatusServiceInterface::class);
        $this->negotiableQuoteModuleConfig =
            $this->createMock(Config::class);

        $this->observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->event = $this->getMockBuilder(Event::class)
            ->setMethods(['getWebsite'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->observer->expects($this->any())->method('getEvent')
            ->willReturn($this->event);

        $objectManager = new ObjectManager($this);
        $this->updateConfig = $objectManager->getObject(
            UpdateConfig::class,
            [
                'storeManager' => $this->storeManager,
                'companyStatusService' => $this->companyStatusService,
                'negotiableQuoteModuleConfig' => $this->negotiableQuoteModuleConfig,

            ]
        );
    }

    /**
     * @param int $eventWebsiteId
     * @param bool $isCompanyActive
     * @param bool $isQuoteActive
     * @return void
     * @dataProvider dataProviderExecute
     */
    public function testExecute($eventWebsiteId, $isCompanyActive, $isQuoteActive)
    {
        $this->event->expects($this->any())->method('getWebsite')->willReturn($eventWebsiteId);

        $website = $this->getMockBuilder(WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->storeManager->expects($this->any())->method('getWebsite')
            ->willReturn($website);

        $this->companyStatusService->expects($this->any())->method('isActive')
            ->willReturn($isCompanyActive);

        $this->negotiableQuoteModuleConfig->expects($this->any())->method('isActive')
            ->willReturn($isQuoteActive);

        $isRequireModuleDisable = !$isCompanyActive && $isQuoteActive;
        $this->negotiableQuoteModuleConfig->expects($this->exactly(
            $isRequireModuleDisable ? 1 : 0
        ))->method('setIsActive');

        $this->updateConfig->execute($this->observer);
    }

    /**
     * @return array
     */
    public function dataProviderExecute()
    {
        return [
            [1, true, true],
            [0, false, true],
            [1, false, false],
            [0, true, false],
        ];
    }
}
