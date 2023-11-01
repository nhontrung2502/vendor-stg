<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Plugin\Sales\Block\Order;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Block\Order\OwnerFilter;
use Magento\NegotiableQuote\Plugin\Sales\Block\Order\HistoryPlugin;
use Magento\Sales\Block\Order\History;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HistoryPluginTest extends TestCase
{
    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var UserContextInterface|MockObject
     */
    private $userContextMock;

    /**
     * @var Collection|MockObject
     */
    private $orderCollectionMock;

    /**
     * @var OwnerFilter|MockObject
     */
    private $ownerFilterBlockMock;

    /**
     * @var HistoryPlugin
     */
    private $historyPlugin;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $createdBy = 1;
        $this->userContextMock = $this->getMockForAbstractClass(
            UserContextInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getUserId']
        );
        $this->requestMock = $this->getMockForAbstractClass(
            RequestInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getParam']
        );
        $this->ownerFilterBlockMock = $this->createMock(OwnerFilter::class);
        $this->requestMock->expects($this->any())->method('getParam')->willReturn($createdBy);
        $this->userContextMock->expects($this->any())->method('getUserId')->willReturn(1);
        $this->ownerFilterBlockMock->expects($this->any())->method('getShowMyParam')->willReturn($createdBy);
        $objectManager = new ObjectManager($this);
        $this->historyPlugin = $objectManager->getObject(
            HistoryPlugin::class,
            [
                'request' => $this->requestMock,
                'userContext' => $this->userContextMock,
                'ownerFilterBlock' => $this->ownerFilterBlockMock,
            ]
        );
    }

    /**
     * Test for method afterGetOrders
     * @param bool|Collection $resultIn
     * @param bool|Collection $resultOut
     * @return void
     * @dataProvider dataProviderAfterGetOrders
     */
    public function testAfterGetOrders($resultIn, $resultOut)
    {
        $historyMock = $this->createMock(History::class);

        $this->assertEquals($this->historyPlugin->afterGetOrders($historyMock, $resultIn), $resultOut);
    }

    /**
     * Data provider afterGetOrders
     *
     * @return array
     */
    public function dataProviderAfterGetOrders()
    {
        $this->orderCollectionMock = $this->createMock(Collection::class);
        return [
            [false, false],
            [$this->orderCollectionMock, $this->orderCollectionMock]
        ];
    }
}
