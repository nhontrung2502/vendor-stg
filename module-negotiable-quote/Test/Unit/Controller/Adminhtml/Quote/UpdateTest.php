<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Adminhtml\Quote;

use Magento\AdvancedCheckout\Model\Cart;
use Magento\AdvancedCheckout\Model\CartFactory;
use Magento\Backend\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Controller\Adminhtml\Quote\Update;
use Magento\NegotiableQuote\Model\NegotiableQuote;
use Magento\NegotiableQuote\Model\Quote\Currency;
use Magento\NegotiableQuote\Model\QuoteUpdater;
use Magento\NegotiableQuote\Model\QuoteUpdatesInfo;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateTest extends TestCase
{
    /**
     * @var QuoteUpdater|MockObject
     */
    private $quoteUpdater;

    /**
     * @var QuoteUpdatesInfo|MockObject
     */
    private $quoteUpdatesInfo;

    /**
     * @var CartFactory|MockObject
     */
    private $cartFactory;

    /**
     * @var Currency|MockObject
     */
    private $quoteCurrency;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var Session|MockObject
     */
    private $session;

    /**
     * @var Json|MockObject
     */
    private $response;

    /**
     * @var Update
     */
    private $update;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->quoteUpdater = $this->createMock(QuoteUpdater::class);
        $this->quoteUpdatesInfo = $this->createMock(QuoteUpdatesInfo::class);
        $this->cartFactory = $this->createPartialMock(CartFactory::class, ['create']);
        $this->quoteCurrency = $this->createMock(Currency::class);
        $this->quoteRepository = $this->getMockForAbstractClass(CartRepositoryInterface::class);
        $this->messageManager = $this->getMockForAbstractClass(ManagerInterface::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->request = $this->getMockForAbstractClass(RequestInterface::class);
        $this->session = $this->createMock(Session::class);
        $this->response = $this->createMock(Json::class);
        $resultFactory = $this->createPartialMock(ResultFactory::class, ['create']);
        $resultFactory->expects($this->once())->method('create')
            ->with(ResultFactory::TYPE_JSON)->willReturn($this->response);

        $objectManager = new ObjectManager($this);
        $this->update = $objectManager->getObject(
            Update::class,
            [
                'quoteUpdater' => $this->quoteUpdater,
                'quoteUpdatesInfo' => $this->quoteUpdatesInfo,
                'cartFactory' => $this->cartFactory,
                'quoteCurrency' => $this->quoteCurrency,
                'quoteRepository' => $this->quoteRepository,
                'resultFactory' => $resultFactory,
                'messageManager' => $this->messageManager,
                '_request' => $this->request,
                '_session' => $this->session,
                'logger' => $this->logger,
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
        $quoteId = 1;
        $quoteData = [];
        $updaterData = ['some_key' => 'Some Data'];
        $updaterMessages = ['Message #1'];
        $this->request->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['quote_id'], ['quote'], ['quote_id'])
            ->willReturnOnConsecutiveCalls($quoteId, $quoteData, $quoteId);
        $this->quoteCurrency->expects($this->once())->method('updateQuoteCurrency')->with($quoteId);
        $this->quoteUpdater->expects($this->once())
            ->method('updateQuote')->with($quoteId, $quoteData + ['items' => []], false)->willReturn(true);
        $quote = $this->getMockForAbstractClass(CartInterface::class);
        $quoteNegotiation = $this->createMock(NegotiableQuote::class);
        $quoteNegotiation->expects($this->once())->method('getIsRegularQuote')->willReturn(true);
        $extensionAttributes = $this->getMockForAbstractClass(
            CartExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getNegotiableQuote']
        );
        $quote->expects($this->once())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $extensionAttributes->expects($this->once())->method('getNegotiableQuote')->willReturn($quoteNegotiation);
        $quoteNegotiation->expects($this->once())->method('getIsRegularQuote')->willReturn(true);
        $this->quoteRepository->expects($this->once())->method('get')->with($quoteId, ['*'])->willReturn($quote);
        $this->quoteUpdatesInfo->expects($this->once())
            ->method('getQuoteUpdatedData')->with($quote, $quoteData + ['items' => []])->willReturn($updaterData);
        $cart = $this->createMock(Cart::class);
        $this->cartFactory->expects($this->once())->method('create')->willReturn($cart);
        $cart->expects($this->once())->method('setSession')->with($this->session)->willReturnSelf();
        $cart->expects($this->once())->method('getFailedItems')->willReturn([]);
        $this->quoteUpdater->expects($this->once())->method('getMessages')->willReturn($updaterMessages);
        $this->response->expects($this->once())->method('setJsonData')->with(
            json_encode(
                $updaterData + [
                    'hasFailedItems' => false,
                    'messages' => $updaterMessages,
                ],
                JSON_NUMERIC_CHECK
            )
        )->willReturnSelf();
        $this->assertEquals($this->response, $this->update->execute());
    }

    /**
     * Test for method execute without quote.
     *
     * @return void
     */
    public function testExecuteWithoutQuote(): void
    {
        $quoteId = 1;
        $quoteData = [];
        $this->request->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['quote_id'], ['quote'], ['quote_id'])
            ->willReturnOnConsecutiveCalls($quoteId, $quoteData, $quoteId);
        $this->quoteCurrency->expects($this->once())->method('updateQuoteCurrency')->with($quoteId);
        $this->quoteUpdater->expects($this->once())
            ->method('updateQuote')->with($quoteId, $quoteData + ['items' => []], false)->willReturn(true);
        $this->quoteRepository->expects($this->once())->method('get')->with($quoteId, ['*'])
            ->willThrowException(new NoSuchEntityException());
        $this->response->expects($this->once())->method('setJsonData')->with(
            json_encode(
                [
                    'messages' => [['type' => 'error', 'text' => __('Requested quote was not found.')]],
                ],
                JSON_NUMERIC_CHECK
            )
        )->willReturnSelf();
        $this->assertEquals($this->response, $this->update->execute());
    }

    /**
     * Test for method execute with exception.
     *
     * @return void
     */
    public function testExecuteWithException(): void
    {
        $quoteId = 1;
        $quoteData = [];
        $updaterData = ['some_key' => 'Some Data'];
        $updaterMessages = ['Error Message'];
        $exception = new NoSuchEntityException();
        $this->request->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['quote_id'], ['quote'], ['quote_id'])
            ->willReturnOnConsecutiveCalls($quoteId, $quoteData, $quoteId);
        $this->quoteCurrency->expects($this->once())->method('updateQuoteCurrency')->with($quoteId);
        $this->quoteUpdater->expects($this->once())->method('updateQuote')
            ->with($quoteId, $quoteData + ['items' => []], false)->willThrowException($exception);
        $this->logger->expects($this->once())->method('critical')->with($exception);
        $this->messageManager->expects($this->once())
            ->method('addErrorMessage')->with(__('Exception occurred during update quote'))->willReturnSelf();
        $quote = $this->getMockForAbstractClass(CartInterface::class);
        $quoteNegotiation = $this->createMock(NegotiableQuote::class);
        $quoteNegotiation->expects($this->once())->method('getIsRegularQuote')->willReturn(true);
        $extensionAttributes = $this->getMockForAbstractClass(
            CartExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getNegotiableQuote']
        );
        $quote->expects($this->once())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $extensionAttributes->expects($this->once())->method('getNegotiableQuote')->willReturn($quoteNegotiation);
        $quoteNegotiation->expects($this->once())->method('getIsRegularQuote')->willReturn(true);
        $this->quoteRepository->expects($this->once())->method('get')->with($quoteId, ['*'])->willReturn($quote);
        $this->quoteUpdatesInfo->expects($this->once())
            ->method('getQuoteUpdatedData')->with($quote, $quoteData + ['items' => []])->willReturn($updaterData);
        $cart = $this->createMock(Cart::class);
        $this->cartFactory->expects($this->once())->method('create')->willReturn($cart);
        $cart->expects($this->once())->method('setSession')->with($this->session)->willReturnSelf();
        $cart->expects($this->once())->method('getFailedItems')->willReturn([]);
        $this->quoteUpdater->expects($this->once())->method('getMessages')->willReturn($updaterMessages);
        $this->response->expects($this->once())->method('setJsonData')->with(
            json_encode(
                $updaterData + [
                    'hasFailedItems' => false,
                    'messages' => $updaterMessages,
                ],
                JSON_NUMERIC_CHECK
            )
        )->willReturnSelf();
        $this->assertEquals($this->response, $this->update->execute());
    }
}
