<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Quote;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Html\Links;
use Magento\Framework\View\Layout;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Controller\Quote\View;
use Magento\NegotiableQuote\Helper\Quote;
use Magento\NegotiableQuote\Model\Quote\ViewAccessInterface;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterfaceFactory;
use Magento\NegotiableQuote\Model\SettingsProvider;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
     * @var PageFactory|MockObject
     */
    private $resultPageFactory;

    /**
     * @var RequestInterface|MockObject
     */
    private $resourse;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var NegotiableQuoteManagementInterface|MockObject
     */
    private $negotiableQuoteManagement;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var RestrictionInterface|MockObject
     */
    private $customerRestriction;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var SettingsProvider|MockObject
     */
    private $settingsProvider;

    /**
     * @var Quote|MockObject
     */
    private $quoteHelper;

    /**
     * @var ResponseInterface|MockObject
     */
    private $response;

    /**
     * @var RestrictionInterfaceFactory|MockObject
     */
    private $restrictionFactory;

    /**
     * @var ViewAccessInterface|MockObject
     */
    private $viewAccess;

    /**
     * @var Session|MockObject
     */
    private $customerSession;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resourse = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getParam',
                'getFullActionName',
                'getRouteName',
                'isDispatched',
            ])
            ->getMockForAbstractClass();
        $this->messageManager = $this->createMock(ManagerInterface::class);
        $this->resultPageFactory = $this->createMock(PageFactory::class);
        $this->quoteRepository = $this->createMock(CartRepositoryInterface::class);
        $this->resourse->expects($this->any())->method('getParam')->with('quote_id')->willReturn(1);
        $this->customerRestriction = $this->createMock(RestrictionInterface::class);
        $redirectFactory = $this->createMock(RedirectFactory::class);
        $redirect = $this->createMock(Redirect::class);
        $redirect->expects($this->any())
            ->method('setPath')->willReturnSelf();
        $redirectFactory->expects($this->any())
            ->method('create')->willReturn($redirect);
        $this->negotiableQuoteManagement = $this->createMock(NegotiableQuoteManagementInterface::class);
        $this->resultFactory = $this->createMock(ResultFactory::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->settingsProvider = $this->createMock(SettingsProvider::class);
        $this->quoteHelper = $this->createMock(Quote::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->restrictionFactory = $this->createMock(RestrictionInterfaceFactory::class);
        $this->viewAccess = $this->createMock(ViewAccessInterface::class);
        $this->customerSession = $this->createMock(Session::class);

        $objectManager = new ObjectManager($this);
        $this->controller = $objectManager->getObject(
            View::class,
            [
                '_request' => $this->resourse,
                'resultFactory' => $this->resultFactory,
                'resultPageFactory' => $this->resultPageFactory,
                'quoteRepository' => $this->quoteRepository,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagement,
                'resultRedirectFactory' => $redirectFactory,
                'customerRestriction' => $this->customerRestriction,
                'storeManager' => $this->storeManager,
                'messageManager' => $this->messageManager,
                'settingsProvider' => $this->settingsProvider,
                'quoteHelper' => $this->quoteHelper,
                '_response' => $this->response,
                'restrictionFactory' => $this->restrictionFactory,
                'viewAccess' => $this->viewAccess,
                'customerSession' => $this->customerSession,
            ]
        );
    }

    /**
     * Test for isAllowed() method.
     *
     * @return void
     */
    public function testIsAllowed(): void
    {
        $this->prepareMocksForIsAllowed();
        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $this->negotiableQuoteManagement->expects($this->atLeastOnce())->method('getNegotiableQuote')
            ->willReturn($quote);
        $this->viewAccess->expects($this->once())->method('canViewQuote')->with($quote)->willReturn(true);

        $this->assertInstanceOf(
            ResponseInterface::class,
            $this->controller->dispatch($this->resourse)
        );
    }

    /**
     * Test for isAllowed() method when view quote does not exist.
     *
     * @return void
     */
    public function testIsAllowedIfQuoteNotExist(): void
    {
        $this->prepareMocksForIsAllowed();
        $this->negotiableQuoteManagement->expects($this->atLeastOnce())->method('getNegotiableQuote')
            ->willThrowException(new NoSuchEntityException());
        $this->viewAccess->expects($this->never())->method('canViewQuote');

        $this->assertInstanceOf(
            ResponseInterface::class,
            $this->controller->dispatch($this->resourse)
        );
    }

    /**
     * Prepare mocks for isAllowed() test.
     *
     * @return void
     */
    private function prepareMocksForIsAllowed(): void
    {
        $this->settingsProvider->expects($this->once())->method('isModuleEnabled')->willReturn(true);
        $this->settingsProvider->expects($this->once())
            ->method('getCurrentUserType')
            ->willReturn(UserContextInterface::USER_TYPE_CUSTOMER);
        $this->customerSession->expects($this->once())->method('authenticate')->willReturn(true);
        $this->quoteHelper->expects($this->once())->method('isEnabled')->willReturn(true);
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function testExecute(): void
    {
        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $this->quoteHelper->expects($this->once())->method('resolveCurrentQuote')->willReturn($quote);

        $page = $this->createPartialMock(
            Page::class,
            ['getConfig', 'getLayout']
        );
        $layout = $this->createPartialMock(Layout::class, ['getBlock']);
        $block = $this->createPartialMock(Links::class, ['setActive']);
        $layout->expects($this->atLeastOnce())->method('getBlock')->willReturn($block);
        $page->expects($this->atLeastOnce())->method('getLayout')->willReturn($layout);
        $this->resultFactory->expects($this->atLeastOnce())
            ->method('create')
            ->with(ResultFactory::TYPE_PAGE)
            ->willReturn($page);

        $result = $this->controller->execute();
        $this->assertInstanceOf(Page::class, $result);
    }

    /**
     * Test for method execute with exception.
     *
     * @return void
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function testExecuteWithException(): void
    {
        $page = $this->createMock(Page::class);
        $this->resultPageFactory->expects($this->any())->method('create')->willReturn($page);

        $this->messageManager->expects($this->once())->method('addErrorMessage')
            ->with(__('Requested quote was not found'));

        $result = $this->controller->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }
}
