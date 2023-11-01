<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Adminhtml\Quote;

use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\NegotiableQuote\Controller\Adminhtml\Quote\AbstractMassAction;
use Magento\NegotiableQuote\Controller\Adminhtml\Quote\MassDeclineCheck;
use Magento\NegotiableQuote\Model\ResourceModel\Quote\Collection;
use Magento\NegotiableQuote\Model\ResourceModel\Quote\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AbstractMassActionTest extends TestCase
{
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
     * @var Redirect|MockObject
     */
    private $redirect;

    /**
     * @var CollectionFactory|MockObject
     */
    private $negotiableQuoteCollectionFactory;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var AbstractMassAction
     */
    private $massAction;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->filter = $this->createMock(Filter::class);
        $this->resultRedirectFactory = $this->createMock(RedirectFactory::class);
        $this->negotiableQuoteCollectionFactory =
            $this->getMockBuilder(CollectionFactory::class)
                ->disableOriginalConstructor()
                ->setMethods(['create'])
                ->getMock();
        $this->negotiableQuoteCollection =
            $this->getMockBuilder(Collection::class)
                ->disableOriginalConstructor()
                ->getMock();
        $this->messageManager =
            $this->getMockBuilder(ManagerInterface::class)
                ->disableOriginalConstructor()
                ->setMethods(['addError', 'addException'])
                ->getMockForAbstractClass();
        $this->redirect = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirect = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $redirectUrl = '*/*/index';
        $this->negotiableQuoteCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->negotiableQuoteCollection);

        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturn($this->resultRedirect);
        $this->resultFactory->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($this->redirect);
        $this->redirect->expects($this->once())->method('setPath')->with($redirectUrl)->willReturnSelf();
        $this->massAction = $objectManagerHelper->getObject(
            MassDeclineCheck::class,
            [
                'filter' => $this->filter,
                'collectionFactory' => $this->negotiableQuoteCollectionFactory,
                'resultFactory' => $this->resultFactory,
                'messageManager' => $this->messageManager
            ]
        );
    }

    /**
     * Test execute with LocalizedException.
     *
     * @return void
     */
    public function testExecuteWithLocalizedException()
    {
        $phrase = new Phrase('Something went wrong.');
        $exception = new LocalizedException($phrase);
        $this->filter->expects($this->once())
            ->method('getCollection')
            ->with($this->negotiableQuoteCollection)
            ->willThrowException($exception);
        $this->messageManager->expects($this->once())->method('addError')->willReturnSelf();
        $this->assertInstanceOf(Redirect::class, $this->massAction->execute());
    }

    /**
     * Test execute with Exception.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $phrase = 'Something went wrong. Please try again later.';
        $exception = new \Exception($phrase);
        $this->filter->expects($this->once())
            ->method('getCollection')
            ->with($this->negotiableQuoteCollection)
            ->willThrowException($exception);
        $this->messageManager->expects($this->once())
            ->method('addException')
            ->with($exception, $phrase)
            ->willReturnSelf();
        $this->assertInstanceOf(Redirect::class, $this->massAction->execute());
    }
}
