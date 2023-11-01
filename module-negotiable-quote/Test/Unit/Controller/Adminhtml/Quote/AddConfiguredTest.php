<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Adminhtml\Quote;

use Exception;
use Magento\AdvancedCheckout\Model\CartFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Controller\Adminhtml\Quote\AddConfigured;
use Magento\NegotiableQuote\Model\NegotiableQuote;
use Magento\NegotiableQuote\Model\Quote\Currency;
use Magento\NegotiableQuote\Model\QuoteUpdater;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AddConfiguredTest extends TestCase
{
    /**
     * @var  AddConfigured
     */
    private $controller;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var Currency|MockObject
     */
    private $quoteCurrency;

    /**
     * @var QuoteUpdater|MockObject
     */
    private $quoteUpdater;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var Raw|MockObject
     */
    private $response;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var CartInterface|MockObject
     */
    private $quote;

    /**
     * @var CartFactory|MockObject
     */
    private $cartFactory;

    /**
     * Set up.
     *
     * @return void
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
        $this->quoteCurrency = $this->createMock(Currency::class);
        $this->quoteUpdater = $this->createMock(QuoteUpdater::class);
        $this->messageManager = $this->getMockForAbstractClass(
            ManagerInterface::class,
            [],
            '',
            false
        );
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class, [], '', false);
        $this->response = $this->createMock(Json::class);
        $resultFactory = $this->createPartialMock(ResultFactory::class, ['create']);
        $resultFactory->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_JSON)
            ->willReturn($this->response);
        $this->quoteRepository = $this->getMockForAbstractClass(
            CartRepositoryInterface::class,
            [],
            '',
            false
        );
        $this->quote = $this->getMockForAbstractClass(
            CartInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['collectTotals', 'setSession', 'getFailedItems']
        );
        $this->quote->expects($this->once())
            ->method('setSession')
            ->willReturnSelf();
        $this->cartFactory = $this->createPartialMock(CartFactory::class, ['create']);
        $this->cartFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->quote);
        $this->quote->expects($this->once())
            ->method('getFailedItems')
            ->willReturn([]);

        $objectManagerHelper = new ObjectManager($this);
        $this->controller = $objectManagerHelper->getObject(
            AddConfigured::class,
            [
                'logger' => $this->logger,
                'quoteRepository' => $this->quoteRepository,
                'messageManager' => $this->messageManager,
                'resultFactory' => $resultFactory,
                'quoteCurrency' => $this->quoteCurrency,
                'quoteUpdater' => $this->quoteUpdater,
                'cartFactory' => $this->cartFactory,
                '_request' => $this->request
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
        $itemId = 2;
        $updaterMessages = ['Message #1'];
        $configuredItems = [$itemId => 'config_value'];
        $addBySku = [$itemId => []];

        $this->request->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['quote_id'], ['dataSend'], ['add_by_sku'], ['item'])
            ->willReturnOnConsecutiveCalls($quoteId, json_encode(null), $addBySku, $configuredItems);
        $this->quoteRepository->expects($this->once())
            ->method('get')
            ->willReturn($this->quote);
        $quoteNegotiation = $this->createMock(NegotiableQuote::class);
        $quoteNegotiation->expects($this->once())
            ->method('getIsRegularQuote')
            ->willReturn(true);
        $extensionAttributes = $this->getMockForAbstractClass(
            CartExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getNegotiableQuote']
        );
        $this->quote->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        $extensionAttributes->expects($this->once())
            ->method('getNegotiableQuote')
            ->willReturn($quoteNegotiation);
        $this->quoteCurrency->expects($this->once())
            ->method('updateQuoteCurrency')
            ->with($quoteId);
        $this->quoteUpdater->expects($this->once())
            ->method('updateQuote')
            ->with(
                $quoteId,
                $quoteData + [
                    'configuredItems' => [
                        $itemId => ['config' => $configuredItems[$itemId]]
                    ]
                ]
            )
            ->willReturn(true);
        $this->quoteUpdater->expects($this->once())
            ->method('getMessages')
            ->willReturn($updaterMessages);
        $this->response->expects($this->once())
            ->method('setJsonData')
            ->with(
                json_encode(
                    [
                        'hasFailedItems' => false,
                        'messages' => $updaterMessages
                    ],
                    JSON_NUMERIC_CHECK
                )
            )
            ->willReturnSelf();

        $this->assertEquals($this->response, $this->controller->execute());
    }

    /**
     * Test for method execute throwing exception.
     *
     * @return void
     */
    public function testExecuteException(): void
    {
        $updaterMessages = ['Error Message'];
        $this->request->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['quote_id'], ['dataSend'], ['add_by_sku'], ['item'])
            ->willReturnOnConsecutiveCalls(null, json_encode(null), [], []);
        $this->quoteRepository->expects($this->once())
            ->method('get')
            ->willReturn($this->quote);
        $quoteNegotiation = $this->createMock(NegotiableQuote::class);
        $quoteNegotiation->expects($this->once())
            ->method('getIsRegularQuote')
            ->willReturn(true);
        $extensionAttributes = $this->getMockForAbstractClass(
            CartExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getNegotiableQuote']
        );
        $this->quote->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        $extensionAttributes->expects($this->once())
            ->method('getNegotiableQuote')
            ->willReturn($quoteNegotiation);
        $exception = new Exception('Exception message');
        $this->quoteUpdater->expects($this->once())
            ->method('updateQuote')
            ->willThrowException($exception);
        $this->logger->expects($this->once())
            ->method('critical')
            ->with($exception);
        $this->messageManager->expects($this->once())
            ->method('addError')
            ->with('Something went wrong');
        $this->quoteUpdater->expects($this->once())
            ->method('getMessages')
            ->willReturn($updaterMessages);
        $this->response->expects($this->once())
            ->method('setJsonData')
            ->with(
                json_encode(
                    [
                        'hasFailedItems' => false,
                        'messages' => $updaterMessages
                    ],
                    JSON_NUMERIC_CHECK
                )
            )
            ->willReturnSelf();

        $this->assertEquals($this->response, $this->controller->execute());
    }
}
