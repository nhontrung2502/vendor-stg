<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Quote;

use Exception;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Controller\Quote\ItemDelete;
use Magento\NegotiableQuote\Model\SettingsProvider;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ItemDeleteTest extends TestCase
{
    /**
     * @var ItemDelete
     */
    private $controller;

    /**
     * @var RequestInterface|MockObject
     */
    private $resource;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

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
     * @var RedirectFactory|MockObject
     */
    private $redirectFactory;

    /**
     * @var SettingsProvider|MockObject
     */
    private $settingsProvider;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->resource = $this->getMockForAbstractClass(RequestInterface::class);
        $this->messageManager = $this
            ->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addSuccess'])
            ->getMockForAbstractClass();
        $this->createRedirectMock();
        $this->negotiableQuoteManagement = $this
            ->getMockBuilder(NegotiableQuoteManagementInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['updateProcessingByCustomerQuoteStatus', 'removeQuoteItem'])
            ->getMockForAbstractClass();
        $this->quoteRepository = $this->getMockForAbstractClass(CartRepositoryInterface::class);
        $this->formKeyValidator =
            $this->createMock(Validator::class);
        $quote = $this->getMockBuilder(Quote::class)
            ->addMethods(['getCustomerId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteRepository->expects($this->any())->method('get')->willReturn($quote);
        $quote->expects($this->any())->method('getCustomerId')->willReturn(1);
        $this->settingsProvider =
            $this->createPartialMock(SettingsProvider::class, ['getCurrentUserId']);
        $this->settingsProvider->expects($this->any())->method('getCurrentUserId')->willReturn(1);
        $objectManager = new ObjectManager($this);
        $this->controller = $objectManager->getObject(
            ItemDelete::class,
            [
                'quoteRepository' => $this->quoteRepository,
                'formKeyValidator' => $this->formKeyValidator,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagement,
                'resultRedirectFactory' => $this->redirectFactory,
                '_request' => $this->resource,
                'messageManager' => $this->messageManager,
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
        $this->resource->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['quote_id'], ['quote_item_id'])
            ->willReturnOnConsecutiveCalls(1, 1);
        $this->negotiableQuoteManagement->expects($this->any())->method('removeQuoteItem');
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
        $this->resource->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['quote_id'], ['quote_item_id'])
            ->willReturnOnConsecutiveCalls(1, 1);
        $this->negotiableQuoteManagement->expects($this->any())->method('removeQuoteItem')
            ->willThrowException(new Exception());
        $this->messageManager->expects($this->never())->method('addSuccess');
        $this->messageManager->expects($this->once())->method('addException');
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
        $this->resource->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['quote_id'], ['quote_item_id'])
            ->willReturnOnConsecutiveCalls(1, 1);
        $ph = new Phrase('test');
        $this->negotiableQuoteManagement->expects($this->any())->method('removeQuoteItem')
            ->willThrowException(new LocalizedException($ph));
        $this->messageManager->expects($this->never())->method('addSuccess');
        $this->messageManager->expects($this->any())->method('addError');
        $result = $this->controller->execute();

        $this->assertInstanceOf(Redirect::class, $result);
    }

    /**
     * @return void
     */
    private function createRedirectMock(): void
    {
        $this->redirectFactory =
            $this->createPartialMock(RedirectFactory::class, ['create']);
        $redirect = $this->createMock(Redirect::class);
        $redirect->expects($this->any())->method('setPath')->willReturnSelf();
        $this->redirectFactory->expects($this->any())->method('create')->willReturn($redirect);
    }
}
