<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PurchaseOrder\Test\Unit\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface as DBAdapterInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\PurchaseOrder\Model\ResourceModel\PurchaseOrder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test For PurchaseOrder Resource Model for PurchaseOrder module
 *
 * @see PurchaseOrder
 */
class PurchaseOrderTest extends TestCase
{
    /**
     * @var ResourceConnection|MockObject
     */
    private $resourcesMock;

    /**
     * @var DBAdapterInterface|MockObject
     */
    private $resourceConnectionMock;

    /**
     * @var PurchaseOrder
     */
    private $purchaseOrderResourceModel;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $objectManagerHelper = new ObjectManagerHelper($this);

        $this->resourcesMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTableName', 'getConnection'])
            ->getMock();

        $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->setMethods(['insertOnDuplicate', 'delete'])
            ->getMock();

        $this->resourcesMock
            ->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->resourceConnectionMock);

        $this->purchaseOrderResourceModel = $objectManagerHelper->getObject(PurchaseOrder::class, [
            '_resources' => $this->resourcesMock
        ]);
    }

    public function testSaveApprovedBy()
    {
        $this->resourcesMock
            ->expects($this->once())
            ->method('getTableName')
            ->with('purchase_order_approved_by')
            ->willReturn('prefix_purchase_order_approved_by');

        $this->resourceConnectionMock
            ->expects($this->once())
            ->method('insertOnDuplicate')
            ->with('prefix_purchase_order_approved_by', ['purchase_order_id' =>  1, 'customer_id' => 2]);

        $this->resourceConnectionMock
            ->expects($this->exactly(2))
            ->method('delete')
            ->withConsecutive(
                [
                    'prefix_purchase_order_approved_by',
                    [
                        'purchase_order_id = ?' =>  1,
                        'customer_id NOT IN (?)' => [2]
                    ]
                ],
                [
                    'prefix_purchase_order_approved_by',
                    [
                        'purchase_order_id = ?' =>  1,
                    ]
                ]
            );

        $this->purchaseOrderResourceModel->saveApprovedBy(1, [2]);

        $this->purchaseOrderResourceModel->saveApprovedBy(1, []);
    }
}
