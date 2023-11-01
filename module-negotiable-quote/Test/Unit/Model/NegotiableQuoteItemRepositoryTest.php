<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\NegotiableQuoteItem;
use Magento\NegotiableQuote\Model\NegotiableQuoteItemRepository;
use Magento\NegotiableQuote\Model\ResourceModel\NegotiableQuoteItem as NegotiableQuoteItemResource;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NegotiableQuoteItemRepositoryTest extends TestCase
{
    /**
     * @var NegotiableQuoteItemRepository
     */
    private $model;

    /**
     * @var NegotiableQuoteItemResource|MockObject
     */
    private $negotiableQuoteItemResource;

    /**
     * @var NegotiableQuoteItem|MockObject
     */
    private $negotiateQuoteItem;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        $this->negotiableQuoteItemResource = $this->createPartialMock(NegotiableQuoteItemResource::class, ['save']);

        $this->negotiateQuoteItem = $this->createMock(NegotiableQuoteItem::class);

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            NegotiableQuoteItemRepository::class,
            [
                'negotiableQuoteItemResource' => $this->negotiableQuoteItemResource,
            ]
        );
    }

    /**
     * Test for method save.
     */
    public function testSave()
    {
        $this->negotiableQuoteItemResource->expects($this->once())
            ->method('save')
            ->willReturn($this->negotiateQuoteItem);

        $this->assertTrue($this->model->save($this->negotiateQuoteItem));
    }

    /**
     * Test for method save throwing exception.
     */
    public function testSaveException()
    {
        $exception = new \Exception();
        $this->expectException(CouldNotSaveException::class);
        $this->negotiableQuoteItemResource->expects($this->once())->method('save')
            ->with($this->negotiateQuoteItem)->willThrowException($exception);

        $this->assertInstanceOf(\Exception::class, $this->model->save($this->negotiateQuoteItem));
    }
}
