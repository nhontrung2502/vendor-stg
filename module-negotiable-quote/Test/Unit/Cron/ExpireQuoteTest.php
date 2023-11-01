<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Cron;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Cron\ExpireQuote;
use Magento\NegotiableQuote\Model\Expiration;
use Magento\NegotiableQuote\Model\Expired\MerchantNotifier;
use Magento\NegotiableQuote\Model\Expired\Provider\ExpiredQuoteList;
use Magento\NegotiableQuote\Model\HistoryManagementInterface;
use Magento\NegotiableQuote\Model\ResourceModel\QuoteGrid;
use Magento\NegotiableQuote\Model\ResourceModel\QuoteGridInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit test for ExpireQuote.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ExpireQuoteTest extends TestCase
{
    /**
     * @var NegotiableQuoteRepositoryInterface|MockObject
     */
    private $negotiableQuoteRepository;

    /**
     * @var QuoteGridInterface|MockObject
     */
    private $quoteGrid;

    /**
     * @var Expiration|MockObject
     */
    private $expiration;

    /**
     * @var HistoryManagementInterface|MockObject
     */
    private $historyManagement;

    /**
     * @var ExpiredQuoteList|MockObject
     */
    private $expiredQuoteList;

    /**
     * @var MerchantNotifier|MockObject
     */
    private $merchantNotifier;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var NegotiableQuoteInterface|MockObject
     */
    private $quote;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializer;

    /**
     * @var ExpireQuote
     */
    private $expiredQuote;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->negotiableQuoteRepository = $this
            ->getMockBuilder(NegotiableQuoteRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['save', 'getList'])
            ->getMockForAbstractClass();
        $this->quote = $this
            ->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getStatus',
                'setExpirationPeriod',
                'setQuoteId',
                'getQuoteId',
                'getId',
                'setSnapshot',
                'getSnapshot',
                'setStatus'
            ])
            ->getMockForAbstractClass();
        $this->quoteGrid = $this->getMockBuilder(QuoteGridInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->expiration = $this->getMockBuilder(Expiration::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->historyManagement = $this
            ->getMockBuilder(HistoryManagementInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'createLog',
                    'updateLog',
                    'closeLog',
                    'updateStatusLog',
                    'getQuoteHistory',
                    'getLogUpdatesList'
                ]
            )
            ->getMockForAbstractClass();
        $this->expiredQuoteList = $this
            ->getMockBuilder(ExpiredQuoteList::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->merchantNotifier = $this->getMockBuilder(MerchantNotifier::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->serializer = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->expiredQuote = $objectManager->getObject(
            ExpireQuote::class,
            [
                'negotiableQuoteRepository' => $this->negotiableQuoteRepository,
                'quoteGrid' => $this->quoteGrid,
                'expiration' => $this->expiration,
                'historyManagement' => $this->historyManagement,
                'expiredQuoteList' => $this->expiredQuoteList,
                'merchantNotifier' => $this->merchantNotifier,
                'logger' => $this->logger,
                'serializer' => $this->serializer,
            ]
        );
    }

    /**
     * Test for method execute().
     *
     * @return void
     */
    public function testExecute()
    {
        $quoteId = 1;
        $extensionAttributes = $this
            ->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();
        $extensionAttributes->expects($this->any())
            ->method('getNegotiableQuote')
            ->willReturn($this->quote);
        $this->quote->expects($this->once())
            ->method('getStatus')
            ->willReturn(NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN);
        $this->quote->expects($this->once())
            ->method('setExpirationPeriod')
            ->willReturnSelf();
        $this->expiration->expects($this->once())
            ->method('retrieveDefaultExpirationDate')
            ->willReturn(new \DateTime());
        $this->quote->expects($this->once())
            ->method('setQuoteId')
            ->with($quoteId)
            ->willReturnSelf();
        $this->quote->expects($this->once())->method('getQuoteId')->willReturn(1);
        $this->negotiableQuoteRepository->expects($this->once())
            ->method('save')
            ->with($this->quote)
            ->willReturn(true);
        $this->quote->expects($this->once())->method('getId')->willReturn($quoteId);
        $this->quote->expects($this->atLeastOnce())->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        $this->expiredQuoteList->expects($this->once())->method('getExpiredQuotes')->willReturn([$this->quote]);
        $this->quoteGrid->expects($this->once())->method('refreshValue')->with(
            QuoteGrid::QUOTE_ID,
            $quoteId,
            QuoteGrid::QUOTE_STATUS,
            NegotiableQuoteInterface::STATUS_EXPIRED
        )->willReturnSelf();
        $this->historyManagement->expects($this->once())
            ->method('updateStatusLog')
            ->with($quoteId, false, true);

        $this->expiredQuote->execute();
    }

    /**
     * Test for method execute() with change status quote.
     *
     * @return void
     */
    public function testExecuteChangeStatus()
    {
        $quoteId = 1;
        $extensionAttributes = $this
            ->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();
        $extensionAttributes->expects($this->any())
            ->method('getNegotiableQuote')
            ->willReturn($this->quote);
        $this->quote->expects($this->once())
            ->method('getStatus')
            ->willReturn(NegotiableQuoteInterface::STATUS_PROCESSING_BY_CUSTOMER);
        $this->quote->expects($this->once())
            ->method('setStatus')
            ->willReturnSelf();
        $this->quote->expects($this->once())
            ->method('setQuoteId')
            ->with($quoteId)
            ->willReturnSelf();
        $this->quote->expects($this->once())->method('getQuoteId')->willReturn(1);
        $snapshotArray = [
            'negotiable_quote' => [
                NegotiableQuoteInterface::QUOTE_STATUS => NegotiableQuoteInterface::STATUS_PROCESSING_BY_CUSTOMER
            ]
        ];
        $this->serializer->expects($this->atLeastOnce())->method('unserialize')
            ->willReturn($snapshotArray);

        $this->negotiableQuoteRepository->expects($this->once())
            ->method('save')
            ->with($this->quote)
            ->willReturn(true);
        $this->quote->expects($this->once())->method('getId')->willReturn($quoteId);
        $this->quote->expects($this->atLeastOnce())->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        $this->expiredQuoteList->expects($this->once())->method('getExpiredQuotes')->willReturn([$this->quote]);
        $this->quoteGrid->expects($this->once())->method('refreshValue')->with(
            QuoteGrid::QUOTE_ID,
            $quoteId,
            QuoteGrid::QUOTE_STATUS,
            NegotiableQuoteInterface::STATUS_EXPIRED
        )->willReturnSelf();
        $this->historyManagement->expects($this->once())
            ->method('updateStatusLog')
            ->with($quoteId, false, true);

        $this->expiredQuote->execute();
    }

    /**
     * Test for method execute() with Exception.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $exception = new \Exception();
        $this->quote->expects($this->once())->method('getId')->willThrowException($exception);
        $this->expiredQuoteList->expects($this->once())->method('getExpiredQuotes')->willReturn([$this->quote]);
        $this->logger->expects($this->once())->method('critical');

        $this->expiredQuote->execute();
    }
}
