<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Test\Unit\Model\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\PurchaseOrder\Model\Notification\Config;
use Magento\PurchaseOrder\Model\Plugin\DisableCommunicationsForPurchaseOrders;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test disable email communications from queue
 */
class DisableCommunicationsForPurchaseOrdersTest extends TestCase
{
    /**
     * @var DisableCommunicationsForPurchaseOrders
     */
    protected $model;

    /**
     * @var ScopeConfigInterface | MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var Config
     */
    protected $configMock;

    /**
     * Prepare testable object
     */
    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockForAbstractClass(
            ScopeConfigInterface::class
        );

        $this->configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new DisableCommunicationsForPurchaseOrders($this->scopeConfigMock);
    }

    /**
     * Test disable email communications from queue
     *
     * @dataProvider prepareDataSourceProvider
     */
    public function testAfterIsEnabledForStoreView(
        $result,
        $finalResult
    ): void {
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('isSetFlag')
            ->willReturn($result);
        $this->assertEquals(
            $finalResult,
            $this->model->afterIsEnabledForStoreView(
                $this->configMock,
                $result
            )
        );
    }

    /**
     * Data provider data source
     * @return array
     */
    public function prepareDataSourceProvider(): array
    {
        return [
            [true, false],
            [false, false]
        ];
    }
}
