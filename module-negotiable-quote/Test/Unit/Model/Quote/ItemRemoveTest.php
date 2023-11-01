<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Quote;

use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Model\Discount\StateChanges\Applier;
use Magento\NegotiableQuote\Model\HistoryManagementInterface;
use Magento\NegotiableQuote\Model\Quote\ItemRemove;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test for Magento\NegotiableQuote\Model\Quote\ItemRemove class.
 */
class ItemRemoveTest extends TestCase
{
    /**
     * @var ItemRemove
     */
    private $itemRemove;

    /**
     * @var NegotiableQuoteRepositoryInterface|MockObject
     */
    private $negotiableQuoteRepository;

    /**
     * @var Applier|MockObject
     */
    private $messageApplier;

    /**
     * @var HistoryManagementInterface|MockObject
     */
    private $historyManagement;

    /**
     * @var Json|MockObject
     */
    private $serializerMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->negotiableQuoteRepository = $this->getMockBuilder(NegotiableQuoteRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->messageApplier = $this->getMockBuilder(Applier::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->historyManagement = $this->getMockBuilder(HistoryManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->serializerMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['serialize', 'unserialize'])
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->itemRemove = $objectManager->getObject(
            ItemRemove::class,
            [
                'negotiableQuoteRepository' => $this->negotiableQuoteRepository,
                'messageApplier' => $this->messageApplier,
                'historyManagement' => $this->historyManagement,
                'serializer' => $this->serializerMock,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * Test setNotificationRemove method without SKUs.
     *
     * @return void
     */
    public function testSetNotificationRemoveWithoutSkus()
    {
        $valueId = 42;

        $this->prepareSerializerMock();
        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSnapshot', 'setSnapshot'])
            ->getMockForAbstractClass();
        $this->negotiableQuoteRepository->expects($this->once())->method('getById')->willReturn($negotiableQuote);
        $negotiableQuote->expects($this->once())->method('getIsRegularQuote')->willReturn(true);
        $addSku = ['adminhtml' => 'sku'];
        $negotiableQuote->expects($this->atLeastOnce())->method('getDeletedSku')->willReturn(null);
        $negotiableQuote->expects($this->atLeastOnce())->method('setDeletedSku')->willReturnSelf();
        $jsonSnapshot = json_encode([
            'items' => [
                $valueId => [
                    'product_id' => $valueId,
                    'sku' => $valueId,
                    'name' => $valueId,
                ]
            ]
        ]);
        $negotiableQuote->expects($this->once())->method('getSnapshot')->willReturn($jsonSnapshot);
        $negotiableQuote->expects($this->once())->method('setSnapshot')->willReturnSelf();
        $negotiableQuote->expects($this->once())->method('getNegotiatedPriceValue')->willReturn(true);
        $negotiableQuote->expects($this->once())->method('setHasUnconfirmedChanges')->willReturnSelf();
        $negotiableQuote->expects($this->once())->method('setIsCustomerPriceChanged')->willReturnSelf();
        $this->negotiableQuoteRepository->expects($this->once())
            ->method('save')
            ->with($negotiableQuote)
            ->willReturn(true);
        $this->serializerMock->expects($this->never())->method('unserialize');

        $this->assertInstanceOf(
            get_class($this->itemRemove),
            $this->itemRemove->setNotificationRemove($valueId, $valueId, $addSku)
        );
    }

    /**
     * Test setNotificationRemove method with SKUs.
     *
     * @return void
     */
    public function testSetNotificationRemoveWithSkus()
    {
        $valueId = 42;

        $this->prepareSerializerMock();
        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSnapshot', 'setSnapshot'])
            ->getMockForAbstractClass();
        $this->negotiableQuoteRepository->expects($this->once())->method('getById')->willReturn($negotiableQuote);
        $negotiableQuote->expects($this->once())->method('getIsRegularQuote')->willReturn(true);
        $productSkus = [
            Area::AREA_ADMINHTML => ['sku'],
            Area::AREA_FRONTEND => ['sku']
        ];
        $negotiableQuote->expects($this->atLeastOnce())->method('getDeletedSku')->willReturn(json_encode($productSkus));
        $negotiableQuote->expects($this->atLeastOnce())->method('setDeletedSku')->willReturnSelf();
        $jsonSnapshot = json_encode([
            'items' => [
                $valueId => [
                    'product_id' => $valueId,
                    'sku' => $valueId,
                    'name' => $valueId,
                ]
            ]
        ]);
        $negotiableQuote->expects($this->once())->method('getSnapshot')->willReturn($jsonSnapshot);
        $negotiableQuote->expects($this->once())->method('setSnapshot')->willReturnSelf();
        $negotiableQuote->expects($this->once())->method('getNegotiatedPriceValue')->willReturn(true);
        $negotiableQuote->expects($this->once())->method('setHasUnconfirmedChanges')->willReturnSelf();
        $negotiableQuote->expects($this->once())->method('setIsCustomerPriceChanged')->willReturnSelf();
        $this->negotiableQuoteRepository->expects($this->once())->method('save')
            ->with($negotiableQuote)->willReturn(true);
        $addSku = ['adminhtml' => 'sku'];

        $this->assertInstanceOf(
            get_class($this->itemRemove),
            $this->itemRemove->setNotificationRemove($valueId, $valueId, $addSku)
        );
    }

    /**
     * Test setNotificationRemove method with invalid snapshot.
     *
     * @return void
     */
    public function testSetNotificationRemoveInvalidSnapshot()
    {
        $valueId = 42;

        $this->prepareSerializerMock();
        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSnapshot'])
            ->getMockForAbstractClass();
        $this->negotiableQuoteRepository->expects($this->once())->method('getById')->willReturn($negotiableQuote);
        $negotiableQuote->expects($this->once())->method('getIsRegularQuote')->willReturn(true);
        $productSkus = [
            Area::AREA_ADMINHTML => ['sku'],
            Area::AREA_FRONTEND => ['sku']
        ];
        $negotiableQuote->expects($this->atLeastOnce())->method('getDeletedSku')->willReturn(json_encode($productSkus));
        $negotiableQuote->expects($this->atLeastOnce())->method('setDeletedSku')->willReturnSelf();
        $jsonInvalidSnapshot = json_encode('notArray');
        $negotiableQuote->expects($this->once())->method('getSnapshot')->willReturn($jsonInvalidSnapshot);
        $addSku = ['adminhtml' => 'sku'];

        $this->assertInstanceOf(
            get_class($this->itemRemove),
            $this->itemRemove->setNotificationRemove($valueId, $valueId, $addSku)
        );
    }

    /**
     * Test setNotificationRemove method with Admin notifications only.
     *
     * @return void
     */
    public function testSetNotificationRemoveForAdminOnly()
    {
        $valueId = 42;

        $this->prepareSerializerMock();
        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSnapshot'])
            ->getMockForAbstractClass();
        $this->negotiableQuoteRepository->expects($this->once())->method('getById')->willReturn($negotiableQuote);
        $negotiableQuote->expects($this->once())->method('getIsRegularQuote')->willReturn(true);
        $productSkus = [Area::AREA_ADMINHTML => ['sku']];
        $negotiableQuote->expects($this->atLeastOnce())->method('getDeletedSku')->willReturn(json_encode($productSkus));
        $negotiableQuote->expects($this->once())->method('setDeletedSku')
            ->with(json_encode($productSkus))
            ->willReturnSelf();
        $jsonInvalidSnapshot = json_encode('notArray');
        $negotiableQuote->expects($this->once())->method('getSnapshot')->willReturn($jsonInvalidSnapshot);
        $addSku = ['sku'];

        $this->assertInstanceOf(
            get_class($this->itemRemove),
            $this->itemRemove->setNotificationRemove($valueId, $valueId, $addSku)
        );
    }

    /**
     * Test setNotificationRemove method with not regular snapshot.
     *
     * @return void
     */
    public function testSetNotificationRemoveNotRegularSnapshot()
    {
        $valueId = 42;

        $this->prepareSerializerMock();
        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->negotiableQuoteRepository->expects($this->once())->method('getById')->willReturn($negotiableQuote);
        $negotiableQuote->expects($this->once())->method('getIsRegularQuote')->willReturn(false);
        $addSku = ['adminhtml' => 'sku'];

        $this->assertInstanceOf(
            get_class($this->itemRemove),
            $this->itemRemove->setNotificationRemove($valueId, $valueId, $addSku)
        );
    }

    /**
     * Prepare Serializer mock for tests.
     *
     * @return void
     */
    private function prepareSerializerMock()
    {
        $this->serializerMock->expects($this->any())->method('serialize')
            ->willReturnCallback(
                function ($value) {
                    return json_encode($value);
                }
            );
        $this->serializerMock->expects($this->any())->method('unserialize')
            ->willReturnCallback(
                function ($value) {
                    return json_decode($value, true);
                }
            );
    }

    /**
     * Test setNotificationRemove method with Exception on quote save.
     *
     * @return void
     */
    public function testSetNotificationRemoveWithExceptionOnQuoteSave()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('Cannot save removed quote item notification');
        $valueId = 42;

        $this->prepareSerializerMock();
        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSnapshot', 'setSnapshot'])
            ->getMockForAbstractClass();
        $this->negotiableQuoteRepository->expects($this->once())->method('getById')->willReturn($negotiableQuote);
        $negotiableQuote->expects($this->once())->method('getIsRegularQuote')->willReturn(true);
        $productSkus = [
            Area::AREA_ADMINHTML => ['sku'],
            Area::AREA_FRONTEND => ['sku']
        ];
        $negotiableQuote->expects($this->atLeastOnce())->method('getDeletedSku')->willReturn(json_encode($productSkus));
        $exception = new LocalizedException(__('exception message'));
        $this->negotiableQuoteRepository->expects($this->once())->method('save')
            ->with($negotiableQuote)->willThrowException($exception);
        $this->loggerMock->expects($this->once())->method('critical')->with('exception message');
        $addSku = ['adminhtml' => 'sku'];

        $this->itemRemove->setNotificationRemove($valueId, $valueId, $addSku);
    }
}
