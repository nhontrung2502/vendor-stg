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
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Controller\FileProcessor;
use Magento\NegotiableQuote\Controller\Quote\Send;
use Magento\NegotiableQuote\Model\Quote\Address;
use Magento\NegotiableQuote\Model\SettingsProvider;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Send.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SendTest extends TestCase
{
    /**
     * @var Send
     */
    private $controller;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

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
     * @var Http|MockObject
     */
    private $resource;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var SettingsProvider|MockObject
     */
    private $settingsProvider;

    /**
     * @var Address|MockObject
     */
    private $negotiableQuoteAddress;

    /**
     * @var FileProcessor|MockObject
     */
    private $fileProcessor;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->resource = $this->createMock(Http::class);
        $this->messageManager = $this->getMockForAbstractClass(ManagerInterface::class);
        $redirectFactory = $this->createPartialMock(
            RedirectFactory::class,
            ['create']
        );
        $redirect = $this->createMock(Redirect::class);
        $redirect->expects($this->any())->method('setPath')->willReturnSelf();
        $redirectFactory->expects($this->any())->method('create')->willReturn($redirect);
        $this->resultFactory = $this->createPartialMock(
            ResultFactory::class,
            ['create']
        );
        $this->negotiableQuoteManagement = $this->createMock(
            NegotiableQuoteManagementInterface::class
        );
        $this->quoteRepository = $this->getMockForAbstractClass(CartRepositoryInterface::class);
        $this->formKeyValidator =
            $this->createMock(Validator::class);
        $quote = $this->getMockBuilder(Quote::class)
            ->addMethods(['getCustomerId'])
            ->disableOriginalConstructor()
            ->getMock();
        $quote->expects($this->any())->method('getCustomerId')->willReturn(1);
        $this->settingsProvider = $this->createPartialMock(
            SettingsProvider::class,
            ['getCurrentUserId']
        );
        $this->negotiableQuoteAddress = $this->createMock(
            Address::class
        );
        $this->fileProcessor = $this->getMockBuilder(FileProcessor::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getFiles'])
            ->getMock();
        $this->settingsProvider->expects($this->any())->method('getCurrentUserId')->willReturn(1);
        $this->quoteRepository->expects($this->any())->method('get')->willReturn($quote);
        $objectManager = new ObjectManager($this);
        $this->controller = $objectManager->getObject(
            Send::class,
            [
                'resultFactory' => $this->resultFactory,
                'quoteRepository' => $this->quoteRepository,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagement,
                'settingsProvider' => $this->settingsProvider,
                'formKeyValidator' => $this->formKeyValidator,
                'negotiableQuoteAddress' => $this->negotiableQuoteAddress,
                '_request' => $this->resource,
                'resultRedirectFactory' => $redirectFactory,
                'fileProcessor' => $this->fileProcessor,
                'messageManager' => $this->messageManager
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
        $this->resource->method('getParam')
            ->withConsecutive(['quote_id'], ['comment'])
            ->willReturnOnConsecutiveCalls(1, 'Test comment');
        $this->formKeyValidator->expects($this->any())->method('validate')->willReturn(true);
        $this->fileProcessor->expects($this->atLeastOnce())->method('getFiles')->willReturn([]);

        $resultPage = $this->getMockForAbstractClass(ResultInterface::class);
        $this->resultFactory->expects($this->any())
            ->method('create')->willReturn($resultPage);
        $this->negotiableQuoteAddress->expects($this->once())->method('updateQuoteShippingAddressDraft')->with(1);
        $this->negotiableQuoteManagement->expects($this->once())->method('send')->willReturn(true);

        $result = $this->controller->execute();

        $this->assertInstanceOf(ResultInterface::class, $result);
    }

    /**
     * Test for method execute without form key.
     *
     * @return void
     */
    public function testExecuteWithoutFormkey(): void
    {
        $this->resource->method('getParam')
            ->withConsecutive(['quote_id'], ['comment'])
            ->willReturnOnConsecutiveCalls(1, 'Test comment');
        $result = $this->controller->execute();

        $this->assertInstanceOf(Redirect::class, $result);
    }

    /**
     * Test for method execute without quote id.
     *
     * @return void
     */
    public function testExecuteWithoutQuoteId(): void
    {
        $this->resource->method('getParam')
            ->withConsecutive(['quote_id'], ['comment'])
            ->willReturnOnConsecutiveCalls(0, 'Test comment');
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
        $this->fileProcessor->expects($this->atLeastOnce())->method('getFiles')->willReturn([]);
        $this->resource->method('getParam')
            ->withConsecutive(['quote_id'], ['comment'])
            ->willReturnOnConsecutiveCalls(1, 'Test comment');
        $this->formKeyValidator->expects($this->any())->method('validate')->willReturn(true);
        $this->negotiableQuoteManagement->expects($this->any())->method('send')
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
        $this->fileProcessor->expects($this->atLeastOnce())->method('getFiles')->willReturn([]);
        $this->resource->method('getParam')
            ->withConsecutive(['quote_id'], ['comment'])
            ->willReturnOnConsecutiveCalls(1, 'Test comment');
        $this->formKeyValidator->expects($this->any())->method('validate')->willReturn(true);
        $ph = new Phrase('test');
        $this->negotiableQuoteManagement->expects($this->any())->method('send')
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
        $this->fileProcessor->expects($this->atLeastOnce())->method('getFiles')->willReturn([]);
        $this->resource->method('getParam')
            ->withConsecutive(['quote_id'], ['comment'])
            ->willReturnOnConsecutiveCalls(1, 'Test comment');
        $this->formKeyValidator->expects($this->any())->method('validate')->willReturn(true);
        $this->negotiableQuoteManagement->expects($this->once())->method('send')->willReturn(false);

        $result = $this->controller->execute();

        $this->assertInstanceOf(Redirect::class, $result);
    }
}
