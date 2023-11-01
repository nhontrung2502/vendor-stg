<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Adminhtml\Quote;

use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Controller\Adminhtml\Quote\View;
use Magento\NegotiableQuote\Helper\Quote;
use Magento\NegotiableQuote\Model\Cart;
use Magento\NegotiableQuote\Model\Discount\StateChanges\Provider;
use Magento\Quote\Api\CartRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ViewTest extends TestCase
{
    /**
     * @var View
     */
    private $controller;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var RedirectFactory|MockObject
     */
    private $redirectFactory;

    /**
     * @var RequestInterface|PHPUnitFrameworkMockObjectMockObject
     */
    private $request;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var NegotiableQuoteManagementInterface|MockObject
     */
    private $negotiableQuoteManagement;

    /**
     * @var Provider|MockObject
     */
    private $messageProvider;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var Cart|MockObject
     */
    private $cartMock;

    /**
     * @var Quote|MockObject
     */
    private $negotiableQuoteHelper;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->request = $this->getMockForAbstractClass(RequestInterface::class);

        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->quoteRepository = $this->getMockForAbstractClass(CartRepositoryInterface::class);
        $logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->request->expects($this->any())->method('getParam')->with('quote_id')->willReturn(1);

        $this->redirectFactory =
            $this->createPartialMock(RedirectFactory::class, ['create']);
        $redirect = $this->createMock(Redirect::class);
        $redirect->expects($this->any())
            ->method('setPath')->willReturnSelf();
        $this->redirectFactory->expects($this->any())
            ->method('create')->willReturn($redirect);
        $this->negotiableQuoteManagement =
            $this->getMockForAbstractClass(NegotiableQuoteManagementInterface::class);
        $objectManager = new ObjectManager($this);
        $this->messageProvider =
            $this->createMock(Provider::class);
        $this->cartMock = $this->createPartialMock(Cart::class, ['removeAllFailed']);

        $this->negotiableQuoteHelper = $this->getMockBuilder(Quote::class)
            ->setMethods(['isLockMessageDisplayed'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->setMethods(['addWarningMessage'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->controller = $objectManager->getObject(
            View::class,
            [
                'resultRedirectFactory' => $this->redirectFactory,
                'request' => $this->request,
                'resultFactory' => $this->resultFactory,
                'logger' => $logger,
                'quoteRepository' => $this->quoteRepository,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagement,
                'messageProvider' => $this->messageProvider,
                'messageManager' => $this->messageManager,
                'cart' => $this->cartMock,
                'negotiableQuoteHelper' => $this->negotiableQuoteHelper
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
        $page = $this->createPartialMock(
            Page::class,
            ['setActiveMenu', 'addBreadcrumb', 'getConfig']
        );
        $this->resultFactory->expects($this->once())->method('create')->willReturn($page);
        $title = $this->createMock(Title::class);
        $config = $this->createMock(Config::class);
        $page->expects($this->any())->method('getConfig')->willReturn($config);
        $config->expects($this->any())->method('getTitle')->willReturn($title);
        $quote = $this->createPartialMock(
            \Magento\Quote\Model\Quote::class,
            [
                'getExtensionAttributes',
                'collectTotals'
            ]
        );

        $this->quoteRepository->expects($this->any())->method('get')->willReturn($quote);
        $this->quoteRepository->expects($this->any())->method('save');

        $this->cartMock->expects($this->once())->method('removeAllFailed');
        $this->messageProvider->expects($this->any())->method('getChangesMessages')->with($quote)
            ->willReturn([1 => 'Message']);

        $this->messageManager->expects($this->exactly(2))->method('addWarningMessage')->willReturnSelf();

        $isLockedMessageDisplayed = true;
        $this->negotiableQuoteHelper->expects(($this->exactly(1)))->method('isLockMessageDisplayed')
            ->willReturn($isLockedMessageDisplayed);

        $result = $this->controller->execute();
        $this->assertInstanceOf(Page::class, $result);
    }

    /**
     * Test for method execute with exception.
     *
     * @return void
     */
    public function testExecuteWithNoSuchEntityException()
    {
        $this->quoteRepository->expects($this->any())
            ->method('get')
            ->willThrowException(new NoSuchEntityException());
        $result = $this->controller->execute();
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Redirect::class, $result);
    }

    /**
     * Test for method execute with exception.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $this->negotiableQuoteManagement->expects($this->any())
            ->method('openByMerchant')->willThrowException(new \Exception());
        $quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteRepository->expects($this->any())->method('get')->willReturn($quote);

        $result = $this->controller->execute();
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Redirect::class, $result);
    }
}
