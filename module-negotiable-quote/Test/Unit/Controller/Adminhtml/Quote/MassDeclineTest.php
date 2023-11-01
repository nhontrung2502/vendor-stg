<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Adminhtml\Quote;

use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Controller\Adminhtml\Quote\MassDecline;
use Magento\NegotiableQuote\Model\ResourceModel\Quote\Collection;
use Magento\NegotiableQuote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Ui\Component\MassAction\Filter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MassDeclineTest extends TestCase
{
    /**
     * @var MassDecline
     */
    private $massAction;

    /**
     * @var RedirectFactory|MockObject
     */
    private $resultRedirectFactory;

    /**
     * @var Redirect|MockObject
     */
    private $resultRedirectMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var Http|MockObject
     */
    private $requestMock;

    /**
     * @var ResponseInterface|MockObject
     */
    private $responseMock;

    /**
     * @var Collection|MockObject
     */
    private $negotiableQuoteCollectionMock;

    /**
     * @var QuoteCollectionFactory|MockObject
     */
    private $negotiableQuoteCollectionFactoryMock;

    /**
     * @var Filter|MockObject
     */
    private $filterMock;

    /**
     * @var NegotiableQuoteManagementInterface|MockObject
     */
    private $negotiableQuoteManagementMock;

    /**
     * @var RestrictionInterface|MockObject
     */
    private $restriction;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->resultRedirectFactory = $this->getMockBuilder(RedirectFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->responseMock = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->negotiableQuoteCollectionMock =
            $this->getMockBuilder(Collection::class)
                ->disableOriginalConstructor()
                ->getMock();
        $this->negotiableQuoteCollectionFactoryMock =
            $this->getMockBuilder(\Magento\NegotiableQuote\Model\ResourceModel\Quote\CollectionFactory::class)
                ->disableOriginalConstructor()
                ->setMethods(['create'])
                ->getMock();
        $redirectMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactory->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($redirectMock);
        $this->resultRedirectMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturn($this->resultRedirectMock);

        $this->filterMock = $this->getMockBuilder(Filter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->filterMock->expects($this->once())
            ->method('getCollection')
            ->with($this->negotiableQuoteCollectionMock)
            ->willReturnArgument(0);
        $this->negotiableQuoteCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->negotiableQuoteCollectionMock);
        $this->negotiableQuoteManagementMock =
            $this->getMockBuilder(NegotiableQuoteManagementInterface::class)
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();

        $this->restriction = $this
            ->getMockBuilder(RestrictionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->massAction = $objectManagerHelper->getObject(
            MassDecline::class,
            [
                'request' => $this->requestMock,
                'response' => $this->responseMock,
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'resultFactory' => $this->resultFactory,
                'filter' => $this->filterMock,
                'collectionFactory' => $this->negotiableQuoteCollectionFactoryMock,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagementMock,
                'restriction' => $this->restriction,
                'quoteRepository' => $this->quoteRepository,
            ]
        );
    }

    /**
     * Test for method execute with available for declining quote.
     */
    public function testExecuteAvailable()
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->negotiableQuoteCollectionMock
            ->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$quote]));
        $this->quoteRepository->expects($this->once())->method('get')->willReturn($quote);
        $this->restriction->expects($this->once())->method('canDecline')->willReturn(true);

        $this->negotiableQuoteManagementMock->expects($this->once())->method('decline');

        $this->resultRedirectMock->expects($this->any())
            ->method('setPath')
            ->with('*/*/index')
            ->willReturnSelf();

        $this->assertInstanceOf(ResultInterface::class, $this->massAction->execute());
    }

    /**
     * Test for method execute with unavailable for declining quote.
     */
    public function testExecuteUnavailable()
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->negotiableQuoteCollectionMock
            ->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$quote]));
        $this->quoteRepository->expects($this->once())->method('get')->willReturn($quote);
        $this->restriction->expects($this->once())->method('canDecline')->willReturn(false);

        $this->negotiableQuoteManagementMock->expects($this->never())->method('decline');

        $this->resultRedirectMock->expects($this->any())
            ->method('setPath')
            ->with('*/*/index')
            ->willReturnSelf();

        $this->assertInstanceOf(ResultInterface::class, $this->massAction->execute());
    }
}
