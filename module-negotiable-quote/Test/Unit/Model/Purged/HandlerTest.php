<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Purged;

use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Model\Purged\Handler;
use Magento\NegotiableQuote\Model\PurgedContentFactory;
use Magento\NegotiableQuote\Model\ResourceModel\PurgedContent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HandlerTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var Handler|MockObject
     */
    private $handler;

    /**
     * @var NegotiableQuoteRepositoryInterface|MockObject
     */
    private $negotiableQuoteRepository;

    /**
     * @var NegotiableQuoteManagementInterface|MockObject
     */
    private $negotiableQuoteManagement;

    /**
     * @var PurgedContent|MockObject
     */
    private $purgedContentResource;

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
        $this->negotiableQuoteRepository = $this
            ->getMockBuilder(NegotiableQuoteRepositoryInterface::class)
            ->setMethods(['getListByCustomerId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->negotiableQuoteManagement = $this
            ->getMockBuilder(NegotiableQuoteManagementInterface::class)
            ->setMethods(['close'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->purgedContentResource = $this
            ->getMockBuilder(PurgedContent::class)
            ->setMethods(['save'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->purgedContentFactory = $this->getMockBuilder(PurgedContentFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->handler = $this->objectManagerHelper->getObject(
            Handler::class,
            [
                'negotiableQuoteRepository' => $this->negotiableQuoteRepository,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagement,
                'purgedContentResource' => $this->purgedContentResource,
                'purgedContentFactory' => $this->purgedContentFactory
            ]
        );
    }

    /**
     * Test process method.
     *
     * @return void
     */
    public function testProcess()
    {
        $contentToStore = [1, 2, 3];
        $userId = 45;
        $closeQuoteAfterProcessing = true;

        $quoteId = 23;
        $quote = $this->getMockBuilder(DataObject::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $quote->expects($this->exactly(3))->method('getId')->willReturn($quoteId);
        $quoteList = [$quote];

        $this->negotiableQuoteRepository->expects($this->exactly(1))
            ->method('getListByCustomerId')->willReturn($quoteList);

        $toParse = '{"test": "test"}';
        $purgedContent = $this->getMockBuilder(\Magento\NegotiableQuote\Model\PurgedContent::class)
            ->setMethods([
                'load',
                'getQuoteId',
                'setQuoteId',
                'getPurgedData',
                'setPurgedData'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $purgedContent->expects($this->exactly(1))->method('load')->willReturnSelf();
        $purgedContent->expects($this->exactly(1))->method('getQuoteId')->willReturn(null);
        $purgedContent->expects($this->exactly(1))->method('setQuoteId')->willReturnSelf();
        $purgedContent->expects($this->exactly(2))->method('getPurgedData')->willReturn($toParse);
        $purgedContent->expects($this->exactly(1))->method('setPurgedData')->willReturnSelf();

        $this->purgedContentFactory->expects($this->exactly(1))->method('create')->willReturn($purgedContent);

        $this->purgedContentResource->expects($this->exactly(1))->method('save')->willReturnSelf();

        $closeStatus = true;
        $this->negotiableQuoteManagement->expects($this->exactly(1))->method('close')->willReturn($closeStatus);

        $this->handler->process($contentToStore, $userId, $closeQuoteAfterProcessing);
    }
}
