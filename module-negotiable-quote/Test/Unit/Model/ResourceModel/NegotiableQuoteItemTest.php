<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteItemInterface;
use Magento\NegotiableQuote\Model\ResourceModel\NegotiableQuoteItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Magento\NegotiableQuote\Model\ResourceModel\NegotiableQuoteItem class.
 */
class NegotiableQuoteItemTest extends TestCase
{
    /**
     * @var NegotiableQuoteItem|MockObject
     */
    private $adapter;

    /**
     * @var NegotiableQuoteItem
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $resource = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->adapter = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $resource->expects($this->once())
            ->method('getTableName')
            ->willReturn(NegotiableQuoteItem::NEGOTIABLE_QUOTE_ITEM_TABLE);
        $resource->expects($this->once())->method('getConnection')->with('default')->willReturn($this->adapter);
        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            NegotiableQuoteItem::class,
            [
                '_resources' => $resource,
            ]
        );
    }

    /**
     * Test saveList method.
     *
     * @return void
     */
    public function testSaveList()
    {
        $item = $this->getMockBuilder(NegotiableQuoteItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMockForAbstractClass();
        $item->expects($this->once())->method('getData')->willReturn(['price' => 10]);
        $this->adapter->expects($this->once())
            ->method('insertOnDuplicate')
            ->with(
                'negotiable_quote_item',
                [['price' => 10]],
                [
                    NegotiableQuoteItemInterface::ORIGINAL_PRICE,
                    NegotiableQuoteItemInterface::ORIGINAL_TAX_AMOUNT,
                    NegotiableQuoteItemInterface::ORIGINAL_DISCOUNT_AMOUNT,
                ]
            )
            ->willReturn(1);
        $this->model->saveList([$item]);
    }
}
