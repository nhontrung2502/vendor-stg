<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Quote;

use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Controller\Quote\Close;
use Magento\NegotiableQuote\Model\SettingsProvider;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CloseTest extends TestCase
{
    /**
     * @var Close
     */
    private $controller;

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
     * @var SettingsProvider|MockObject
     */
    private $settingsProvider;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->messageManager = $this->getMockForAbstractClass(ManagerInterface::class);
        $redirectFactory =
            $this->createPartialMock(RedirectFactory::class, ['create']);
        $redirect = $this->createMock(Redirect::class);
        $redirect->expects($this->any())->method('setPath')->willReturnSelf();
        $redirectFactory->expects($this->any())->method('create')->willReturn($redirect);
        $this->negotiableQuoteManagement =
            $this->getMockForAbstractClass(NegotiableQuoteManagementInterface::class);
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
        $objectManager = new ObjectManager($this);
        $this->controller = $objectManager->getObject(
            Close::class,
            [
                'quoteRepository' => $this->quoteRepository,
                'formKeyValidator' => $this->formKeyValidator,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagement,
                'messageManager' => $this->messageManager,
                'resultRedirectFactory' => $redirectFactory,
                'settingsProvider' => $this->settingsProvider
            ]
        );
    }

    /**
     * Test for method execute
     */
    public function testExecute()
    {
        $this->formKeyValidator->expects($this->any())->method('validate')->willReturn(true);
        $this->settingsProvider->expects($this->once())->method('getCurrentUserId')->willReturn(1);
        $this->negotiableQuoteManagement->expects($this->once())->method('close')->willReturn(true);
        $this->messageManager->expects($this->once())->method('addSuccess');

        $result = $this->controller->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    /**
     * Test for method execute without form key
     */
    public function testExecuteWithoutFormkey()
    {
        $result = $this->controller->execute();

        $this->assertInstanceOf(Redirect::class, $result);
    }

    /**
     * Test for method execute with exception
     */
    public function testExecuteWithException()
    {
        $this->formKeyValidator->expects($this->any())->method('validate')->willReturn(true);
        $this->settingsProvider->expects($this->once())->method('getCurrentUserId')->willReturn(1);
        $this->negotiableQuoteManagement->expects($this->any())->method('close')
            ->willThrowException(new \Exception());
        $this->messageManager->expects($this->never())->method('addSuccess');
        $this->messageManager->expects($this->once())->method('addError');

        $result = $this->controller->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    /**
     * Test for method execute with localized exception
     */
    public function testExecuteWithLocalizedException()
    {
        $this->formKeyValidator->expects($this->any())->method('validate')->willReturn(true);
        $this->settingsProvider->expects($this->once())->method('getCurrentUserId')->willReturn(1);
        $ph = new Phrase('test');
        $this->negotiableQuoteManagement->expects($this->any())->method('close')
            ->willThrowException(new LocalizedException($ph));
        $this->messageManager->expects($this->never())->method('addSuccess');
        $this->messageManager->expects($this->once())->method('addError');

        $result = $this->controller->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }
}
