<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Quote;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Html\Links;
use Magento\Framework\View\Layout;
use Magento\Framework\View\Result\Page;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Controller\Quote\PrintAction;
use Magento\NegotiableQuote\Helper\Quote as QuoteHelper;
use Magento\NegotiableQuote\Model\Quote\Address;
use Magento\NegotiableQuote\Model\Quote\ViewAccessInterface;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\NegotiableQuote\Model\SettingsProvider;
use Magento\Quote\Api\CartRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests \Magento\NegotiableQuote\Controller\Quote\PrintAction class
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PrintActionTest extends TestCase
{
    /**
     * @var PrintAction
     */
    private $controller;

    /**
     * @var QuoteHelper|MockObject
     */
    private $quoteHelper;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var RequestInterface|MockObject
     */
    private $resource;

    /**
     * @var SettingsProvider|MockObject
     */
    private $settingsProvider;

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
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var RedirectFactory|MockObject
     */
    private $redirectFactory;

    /**
     * @var Address|MockObject
     */
    private $address;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resource = $this->getMockForAbstractClass(RequestInterface::class);
        $this->settingsProvider = $this->createMock(SettingsProvider::class);
        $this->quoteRepository = $this->getMockForAbstractClass(CartRepositoryInterface::class);
        $this->customerRestriction = $this->getMockForAbstractClass(RestrictionInterface::class);
        $this->resultFactory = $this->createPartialMock(ResultFactory::class, ['create']);
        $this->redirectFactory = $this->createPartialMock(RedirectFactory::class, ['create']);
        $this->negotiableQuoteManagement = $this->getMockBuilder(NegotiableQuoteManagementInterface::class)
            ->setMethods(['prepareForOpen'])
            ->getMockForAbstractClass();
        $this->quoteHelper = $this->createPartialMock(QuoteHelper::class, ['resolveCurrentQuote']);
        $this->address = $this->createPartialMock(
            Address::class,
            ['updateQuoteShippingAddressDraft']
        );
        $viewAccess = $this->getMockForAbstractClass(ViewAccessInterface::class);
        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->setMethods(['addError'])
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);

        $this->controller = $objectManager->getObject(
            PrintAction::class,
            [
                'request' => $this->resource,
                'resultRedirectFactory' => $this->redirectFactory,
                'quoteHelper' => $this->quoteHelper,
                'quoteRepository' => $this->quoteRepository,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagement,
                'customerRestriction' => $this->customerRestriction,
                'settingsProvider' => $this->settingsProvider,
                'negotiableQuoteAddress' => $this->address,
                'resultFactory' => $this->resultFactory,
                'viewAccess' => $viewAccess,
                'messageManager' => $this->messageManager,
            ]
        );
    }

    /**
     * @return void
     */
    public function testExecuteAccessibleQuote(): void
    {
        $quoteId = 1;
        $quote = $this->createPartialMock(\Magento\Quote\Model\Quote::class, ['getId']);
        $quote->expects($this->once())
            ->method('getId')
            ->willReturn($quoteId);

        $this->quoteHelper->expects($this->once())
            ->method('resolveCurrentQuote')
            ->willReturn($quote);
        $this->address->expects($this->once())
            ->method('updateQuoteShippingAddressDraft')
            ->with($quoteId);

        $block = $this->createPartialMock(Links::class, ['setActive']);
        $block->expects($this->once())
            ->method('setActive')
            ->with('negotiable_quote/quote');

        $layout = $this->createPartialMock(Layout::class, ['getBlock']);
        $layout->expects($this->once())
            ->method('getBlock')
            ->with('customer_account_navigation')
            ->willReturn($block);

        $page = $this->createPartialMock(
            Page::class,
            ['getConfig', 'getLayout']
        );
        $page->expects($this->any())->method('getLayout')->willReturn($layout);
        $this->resultFactory
            ->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_PAGE)
            ->willReturn($page);

        $result = $this->controller->execute();
        $this->assertInstanceOf(Page::class, $result);
    }

    /**
     * @return void
     */
    public function testExecuteNotAccessibleQuote(): void
    {
        $this->quoteHelper->expects($this->once())
            ->method('resolveCurrentQuote')
            ->willReturn(null);
        $this->messageManager->expects($this->once())
            ->method('addError')
            ->with(__('Requested quote was not found'));
        $redirect = $this->createPartialMock(Redirect::class, ['setPath']);
        $redirect->expects($this->any())->method('setPath')->willReturnSelf();
        $this->redirectFactory->expects($this->any())->method('create')->willReturn($redirect);

        $result = $this->controller->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }
}
