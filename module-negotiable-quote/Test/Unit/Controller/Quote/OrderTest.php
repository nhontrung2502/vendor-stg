<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Quote;

use Exception;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Controller\Quote\Order;
use Magento\NegotiableQuote\Model\SettingsProvider;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderTest extends TestCase
{
    /**
     * @var Order
     */
    private $controller;

    /**
     * @var PageFactory|MockObject
     */
    private $resultPageFactory;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var NegotiableQuoteManagementInterface|MockObject
     */
    private $negotiableQuoteManagement;

    /**
     * @var Validator|MockObject
     */
    private $formKeyValidator;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var SettingsProvider|MockObject
     */
    private $settingsProvider;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $resource = $this->createMock(Http::class);
        $resource->expects($this->any())
            ->method('getParam')
            ->with('quote_id')
            ->willReturn(1);
        $this->messageManager = $this->getMockForAbstractClass(ManagerInterface::class);
        $redirectFactory = $this->createPartialMock(
            RedirectFactory::class,
            ['create']
        );
        $redirect = $this->createMock(Redirect::class);
        $redirect->expects($this->any())->method('setPath')->willReturnSelf();
        $redirectFactory->expects($this->any())->method('create')->willReturn($redirect);
        $this->resultPageFactory = $this->createPartialMock(
            PageFactory::class,
            ['create']
        );
        $this->negotiableQuoteManagement = $this->createMock(
            NegotiableQuoteManagementInterface::class
        );
        $this->settingsProvider = $this->createPartialMock(
            SettingsProvider::class,
            ['getCurrentUserId']
        );
        $this->quoteRepository = $this->getMockForAbstractClass(CartRepositoryInterface::class);
        $this->formKeyValidator =
            $this->createMock(Validator::class);
        $quote = $this->getMockBuilder(Quote::class)
            ->addMethods(['getCustomerId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->settingsProvider->expects($this->any())->method('getCurrentUserId')->willReturn(1);
        $quote->expects($this->any())->method('getCustomerId')->willReturn(1);
        $this->quoteRepository->expects($this->any())->method('get')->willReturn($quote);
        $objectManager = new ObjectManager($this);
        $this->controller = $objectManager->getObject(
            Order::class,
            [
                'resultPageFactory' => $this->resultPageFactory,
                'quoteRepository' => $this->quoteRepository,
                'formKeyValidator' => $this->formKeyValidator,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagement,
                'messageManager' => $this->messageManager,
                'resultRedirectFactory' => $redirectFactory,
                '_request' => $resource,
                'settingsProvider' => $this->settingsProvider
            ]
        );
    }

    /**
     * Test for method execute.
     *
     * @return void
     */
    public function testExecute(): void
    {
        $this->formKeyValidator->expects($this->any())->method('validate')->willReturn(true);
        $resultPage = $this->createMock(Page::class);
        $this->resultPageFactory->expects($this->any())
            ->method('create')->willReturn($resultPage);
        $this->negotiableQuoteManagement->expects($this->once())->method('order')->willReturn(true);
        $this->messageManager->expects($this->any())->method('addSuccess');
        $result = $this->controller->execute();

        $this->assertInstanceOf(Redirect::class, $result);
    }

    /**
     * Test for method execute without form key.
     *
     * @return void
     */
    public function testExecuteWithoutFormkey(): void
    {
        $result = $this->controller->execute();

        $this->assertInstanceOf(Redirect::class, $result);
    }

    /**
     * Test for method execute with exception.
     *
     * @return void
     */
    public function testExecuteWithException(): void
    {
        $this->formKeyValidator->expects($this->any())->method('validate')->willReturn(true);
        $this->negotiableQuoteManagement->expects($this->once())->method('order')
            ->willThrowException(new Exception());
        $this->messageManager->expects($this->once())->method('addError');
        $result = $this->controller->execute();

        $this->assertInstanceOf(Redirect::class, $result);
    }

    /**
     * Test for method execute with localized exception.
     *
     * @return void
     */
    public function testExecuteWithLocalizedException(): void
    {
        $this->formKeyValidator->expects($this->any())->method('validate')->willReturn(true);
        $ph = new Phrase('test');
        $this->negotiableQuoteManagement->expects($this->once())->method('order')
            ->willThrowException(new LocalizedException($ph));
        $this->messageManager->expects($this->once())->method('addError');
        $result = $this->controller->execute();

        $this->assertInstanceOf(Redirect::class, $result);
    }

    /**
     * Test for method execute with customer.
     *
     * @return void
     */
    public function testExecuteWithCustomer(): void
    {
        $this->formKeyValidator->expects($this->any())->method('validate')->willReturn(true);
        $this->negotiableQuoteManagement->expects($this->once())->method('order')->willReturn(false);
        $result = $this->controller->execute();

        $this->assertInstanceOf(Redirect::class, $result);
    }
}
