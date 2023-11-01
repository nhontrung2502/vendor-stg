<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Adminhtml\Quote;

use Exception;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Layout;
use Magento\Framework\View\Result\Page;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface as NegotiableQuote;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Controller\Adminhtml\Quote\ShippingMethod;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ShippingMethodTest extends TestCase
{
    /**
     * @var ShippingMethod
     */
    private $controller;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var Page|MockObject
     */
    private $page;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var ActionFlag|MockObject
     */
    private $actionFlag;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        $request = $this->getMockForAbstractClass(RequestInterface::class);
        $this->messageManager = $this->getMockForAbstractClass(ManagerInterface::class);
        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $resultRawFactory = $this->createPartialMock(
            RawFactory::class,
            ['create']
        );
        $this->quoteRepository = $this->getMockForAbstractClass(CartRepositoryInterface::class);
        $logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $request->expects($this->any())
            ->method('getParam')
            ->with('quote_id')
            ->willReturn(1);
        $dataObjectHelper = $this->createMock(DataObjectHelper::class);
        $negotiableQuoteRepository = $this->createMock(NegotiableQuoteRepositoryInterface::class);
        $this->actionFlag = $this->createMock(ActionFlag::class);
        $negotiableQuoteManagement = $this->createMock(NegotiableQuoteManagementInterface::class);

        $objectManager = new ObjectManager($this);
        $this->controller = $objectManager->getObject(
            ShippingMethod::class,
            [
                'request' => $request,
                'messageManager' => $this->messageManager,
                'resultFactory' => $this->resultFactory,
                'logger' => $logger,
                'quoteRepository' => $this->quoteRepository,
                'resultRawFactory' => $resultRawFactory,
                'dataObjectHelper' => $dataObjectHelper,
                'negotiableQuoteRepository' => $negotiableQuoteRepository,
                'negotiableQuoteManagement' => $negotiableQuoteManagement,
                '_actionFlag' => $this->actionFlag
            ]
        );
        $resultRaw = $this->createPartialMock(Raw::class, ['setContents']);
        $resultRaw->expects($this->any())
            ->method('setContents')
            ->willReturn($resultRaw);
        $resultRawFactory->expects($this->any())
            ->method('create')
            ->willReturn($resultRaw);
        $this->page = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->addMethods(['setActiveMenu'])
            ->onlyMethods(['addHandle', 'getLayout'])
            ->getMock();
    }

    /**
     * Positive execute() test.
     *
     * @return void
     */
    public function testExecute(): void
    {
        $this->resultFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->page);
        $this->mockQuote();
        $layoutMock = $this->getMockBuilder(Layout::class)
            ->disableOriginalConstructor()
            ->getMock();
        $layoutMock->expects($this->any())
            ->method('renderElement')
            ->willReturn('ok');
        $this->page->expects($this->any())
            ->method('getLayout')
            ->willReturn($layoutMock);

        $this->messageManager->expects($this->never())
            ->method('addErrorMessage');

        $result = $this->controller->execute();
        $this->assertInstanceOf(ResultInterface::class, $result);
    }

    /**
     * execute() test with exception.
     *
     * @return void
     */
    public function testExecuteWithException(): void
    {
        $this->resultFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->page);
        $this->mockQuote();
        $this->page->expects($this->any())
            ->method('getLayout')
            ->willThrowException(new Exception());
        $this->messageManager->expects($this->once())
            ->method('addErrorMessage');

        $result = $this->controller->execute();
        $this->assertInstanceOf(ResultInterface::class, $result);
    }

    /**
     * Test execute with getQuote NoSuchEntityException thrown.
     *
     * @param NoSuchEntityException|InputException $exception
     *
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecuteWithCreateQuoteException(LocalizedException $exception): void
    {
        $this->quoteRepository->expects($this->any())
            ->method('get')
            ->willThrowException($exception);
        $this->actionFlag->expects($this->once())
            ->method('set')
            ->with('', 'no-dispatch', true);

        $result = $this->controller->execute();
        $this->assertInstanceOf(ResultInterface::class, $result);
    }

    /**
     * Data provider for testExecuteWithCreateQuoteException.
     *
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            [new NoSuchEntityException()],
            [new InputException()]
        ];
    }

    /**
     * Mock quote.
     *
     * @return void
     */
    private function mockQuote(): void
    {
        $quote = $this->createPartialMock(
            Quote::class,
            ['getExtensionAttributes', 'getId', 'getShippingAddress']
        );
        $quoteNegotiation = $this->createMock(\Magento\NegotiableQuote\Model\NegotiableQuote::class);
        $quoteNegotiation->expects($this->any())
            ->method('getIsRegularQuote')
            ->willReturn(true);
        $quoteNegotiation->expects($this->any())
            ->method('getStatus')
            ->willReturn(NegotiableQuote::STATUS_CREATED);
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();
        $extensionAttributes->expects($this->any())
            ->method('getNegotiableQuote')
            ->willReturn($quoteNegotiation);
        $quote->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        $this->quoteRepository->expects($this->any())
            ->method('get')
            ->willReturn($quote);
    }
}
