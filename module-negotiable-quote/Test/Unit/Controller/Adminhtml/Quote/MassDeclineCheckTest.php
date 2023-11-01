<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Adminhtml\Quote;

use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Controller\Adminhtml\Quote\MassDeclineCheck;
use Magento\NegotiableQuote\Model\ResourceModel\Quote\Collection;
use Magento\NegotiableQuote\Model\ResourceModel\Quote\CollectionFactory;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Ui\Component\MassAction\Filter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MassDeclineCheckTest extends TestCase
{
    /**
     * @var string
     */
    private $actionName = 'DeclineCheck';

    /**
     * @var \Magento\NegotiableQuote\Controller\Adminhtml\Quote\Mass|MockObject
     */
    private $massAction;

    /**
     * @var RedirectFactory|MockObject
     */
    private $resultRedirectFactory;

    /**
     * @var Redirect|MockObject
     */
    private $resultRedirect;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var Collection|MockObject
     */
    private $negotiableQuoteCollection;

    /**
     * @var Filter|MockObject
     */
    private $filter;

    /**
     * @var Json|MockObject
     */
    private $resultJson;

    /**
     * @var RestrictionInterface|MockObject
     */
    private $restriction;

    /**
     * @var Redirect|MockObject
     */
    private $redirect;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        if (empty($this->actionName)) {
            return;
        }
        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->resultRedirectFactory = $this->createMock(RedirectFactory::class);

        $this->negotiableQuoteCollection =
            $this->getMockBuilder(Collection::class)
                ->disableOriginalConstructor()
                ->getMock();
        $negotiableQuoteCollectionFactory =
            $this->getMockBuilder(CollectionFactory::class)
                ->disableOriginalConstructor()
                ->setMethods(['create'])
                ->getMock();
        $this->redirect = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactory->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($this->redirect);
        $this->resultRedirect = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturn($this->resultRedirect);

        $this->filter = $this->createMock(Filter::class);
        $this->filter->expects($this->once())
            ->method('getCollection')
            ->with($this->negotiableQuoteCollection)
            ->willReturnArgument(0);
        $negotiableQuoteCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->negotiableQuoteCollection);

        $negotiableQuoteManagement = $this->getMockForAbstractClass(
            NegotiableQuoteManagementInterface::class,
            [],
            '',
            false
        );
        $resultJsonFactory = $this->createMock(JsonFactory::class);
        $this->resultJson = $this->createMock(Json::class);
        $resultJsonFactory->expects($this->any())->method('create')->willReturn($this->resultJson);

        $this->quoteRepository = $this->getMockForAbstractClass(
            CartRepositoryInterface::class,
            [],
            '',
            false
        );
        $this->restriction = $this->getMockForAbstractClass(RestrictionInterface::class, [], '', false);

        $this->massAction = $objectManagerHelper->getObject(
            MassDeclineCheck::class,
            [
                'filter' => $this->filter,
                'collectionFactory' => $negotiableQuoteCollectionFactory,
                'negotiableQuoteManagement' => $negotiableQuoteManagement,
                'resultJsonFactory' => $resultJsonFactory,
                'quoteRepository' => $this->quoteRepository,
                'restriction' => $this->restriction,
                'resultFactory' => $this->resultFactory,
            ]
        );
    }

    /**
     * Test for method execute.
     *
     * @return void
     */
    public function testExecute()
    {
        $quoteId = 123;
        $quote = $this->getMockForAbstractClass(CartInterface::class);
        $quote->expects($this->once())->method('getId')->willReturn($quoteId);
        $this->quoteRepository->expects($this->any())->method('get')->willReturn($quote);
        $testData = [$this->createMock(Quote::class)];

        $this->restriction->expects($this->any())->method('canDecline')->willReturn(true);
        $response = new DataObject();
        $response->setData('items', [$quoteId]);
        $this->resultJson->expects($this->once())->method('setData')->with($response)->willReturnSelf();

        $this->negotiableQuoteCollection
            ->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($testData));

        $this->assertInstanceOf(ResultInterface::class, $this->massAction->execute());
    }
}
