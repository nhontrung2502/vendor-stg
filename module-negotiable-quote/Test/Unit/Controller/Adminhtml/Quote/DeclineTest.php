<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Adminhtml\Quote;

use Magento\Backend\Model\View\Result\RedirectFactory as BackendRedirectFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Result\PageFactory;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Controller\Adminhtml\Quote\Decline;
use Magento\NegotiableQuote\Model\NegotiableQuote;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DeclineTest extends TestCase
{
    /**
     * @var PageFactory|MockObject
     */
    private $resultPageFactory;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory|MockObject
     */
    private $resultRedirectFactory;

    /**
     * @var Redirect|MockObject
     */
    private $resultRedirect;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var NegotiableQuoteManagementInterface|MockObject
     */
    private $negotiableQuoteManagement;

    /**
     * @var Decline|MockObject
     */
    private $decline;

    /**
     * @var CartInterface|MockObject
     */
    private $quote;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->request = $this->getMockForAbstractClass(
            RequestInterface::class,
            ['getParam'],
            '',
            false,
            false,
            true,
            []
        );
        $this->resultPageFactory =
            $this->createPartialMock(PageFactory::class, ['create']);
        $this->resultRedirectFactory =
            $this->createPartialMock(BackendRedirectFactory::class, ['create']);
        $this->resultRedirect =
            $this->createPartialMock(Redirect::class, ['setPath']);
        $this->logger = $this->getMockForAbstractClass(
            LoggerInterface::class,
            ['critical'],
            '',
            false,
            false,
            true,
            []
        );
        $this->quoteRepository = $this->getMockForAbstractClass(
            CartRepositoryInterface::class,
            ['get'],
            '',
            false,
            false,
            true,
            []
        );
        $this->negotiableQuoteManagement =
            $this->getMockForAbstractClass(NegotiableQuoteManagementInterface::class);
        $this->messageManager = $this->getMockForAbstractClass(
            ManagerInterface::class,
            ['addError'],
            '',
            false,
            false,
            true,
            []
        );
        $this->objectManager = new ObjectManager($this);
        $this->decline = $this->objectManager->getObject(
            Decline::class,
            [
                'request' => $this->request,
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'resultPageFactory' => $this->resultPageFactory,
                'logger' => $this->logger,
                'quoteRepository' => $this->quoteRepository,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagement
            ]
        );
    }

    /**
     * Test form method execute().
     */
    public function testExecute()
    {
        $this->createQuote();
        $this->negotiableQuoteManagement->expects($this->once())->method('decline');
        $this->getRedirect();

        $this->assertInstanceOf(ResultInterface::class, $this->decline->execute());
    }

    /**
     * Test form method execute() throwing exception.
     */
    public function testExecuteWithException()
    {
        $exception = new \Exception('test message');
        $this->createQuote();
        $this->negotiableQuoteManagement->expects($this->once())
            ->method('decline')->willThrowException($exception);
        $this->logger->expects($this->once())
            ->method('critical')
            ->with($exception);
        $this->messageManager->expects($this->any())->method('addError');
        $this->getRedirect();

        $this->assertInstanceOf(ResultInterface::class, $this->decline->execute());
    }

    /**
     * Makes sure $quote evaluates to true.
     */
    private function createQuote()
    {
        $isRegular = true;
        $negotiableQuote =
            $this->createPartialMock(NegotiableQuote::class, ['getIsRegularQuote']);
        $negotiableQuote->expects($this->any())
            ->method('getIsRegularQuote')
            ->willReturn($isRegular);
        $extension = $this->getMockForAbstractClass(
            CartExtensionInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getNegotiableQuote']
        );
        $extension->expects($this->any())
            ->method('getNegotiableQuote')
            ->willReturn($negotiableQuote);
        $this->quote = $this->getMockForAbstractClass(
            CartInterface::class,
            ['getExtensionAttributes', 'getId'],
            '',
            false,
            false,
            true,
            ['getAppliedRuleIds']
        );
        $this->quote->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($extension);
        $this->quote->expects($this->any())
            ->method('getAppliedRuleIds')
            ->willReturn('1');
        $this->quoteRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->quote);
    }

    /**
     * Makes sure Magento\NegotiableQuote\Controller\Adminhtml\Quote::getRedirect() works properly.
     */
    private function getRedirect()
    {
        $quoteId = 1;
        $this->resultRedirectFactory->expects($this->any())->method('create')->willReturn($this->resultRedirect);
        $this->quote->expects($this->any())->method('getId')->willReturn($quoteId);
        $this->resultRedirect->expects($this->any())
            ->method('setPath')
            ->with('quotes/quote/view', ['quote_id' =>$quoteId])
            ->willReturnSelf();
    }
}
