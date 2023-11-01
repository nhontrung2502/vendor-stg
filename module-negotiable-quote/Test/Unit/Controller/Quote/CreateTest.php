<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Quote;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Controller\FileProcessor;
use Magento\NegotiableQuote\Controller\Quote\Create;
use Magento\NegotiableQuote\Helper\Config;
use Magento\NegotiableQuote\Model\CommentManagementInterface;
use Magento\NegotiableQuote\Model\SettingsProvider;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit test for Create.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreateTest extends TestCase
{
    /**
     * @var \Magento\NegotiableQuote\Controller\Quote\Create
     */
    private $controller;

    /**
     * @var RequestInterface|MockObject
     */
    private $resource;

    /**
     * @var NegotiableQuoteManagementInterface|MockObject
     */
    private $negotiableQuoteManagement;

    /**
     * @var Session|MockObject
     */
    private $checkoutSession;

    /**
     * @var SettingsProvider|MockObject
     */
    private $settingsProvider;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var CartInterface|MockObject
     */
    private $quote;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->resource = $this->createMock(Http::class);
        $this->settingsProvider = $this->createPartialMock(
            SettingsProvider::class,
            ['retrieveJsonError', 'retrieveJsonSuccess']
        );
        $fileProcessor = $this->getMockBuilder(FileProcessor::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFiles'])
            ->getMock();
        $fileProcessor->expects($this->atLeastOnce())->method('getFiles')->willReturn(
            $this->getAttachmentFields()
        );
        $this->checkoutSession = $this->createMock(Session::class);
        $config = $this->createMock(Config::class);
        $resultJsonFactory =
            $this->createPartialMock(JsonFactory::class, ['create']);
        $this->negotiableQuoteManagement = $this
            ->getMockBuilder(NegotiableQuoteManagementInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();
        $this->quote = $this->getMockForAbstractClass(
            CartInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getData', 'getShippingAddress', 'getBillingAddress', 'getItemsCollection', 'removeAddress']
        );
        $quoteRepository = $this->getMockForAbstractClass(CartRepositoryInterface::class);
        $quoteRepository->expects($this->atLeastOnce())->method('get')->willReturn($this->quote);
        $commentManagement = $this
            ->getMockBuilder(CommentManagementInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'getFilesNamesList'])
            ->getMockForAbstractClass();
        $logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->messageManager = $this->getMockForAbstractClass(ManagerInterface::class);

        $objectManager = new ObjectManager($this);
        $this->controller = $objectManager->getObject(
            Create::class,
            [
                'resultJsonFactory' => $resultJsonFactory,
                'quoteRepository' => $quoteRepository,
                'configHelper' => $config,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagement,
                'checkoutSession' => $this->checkoutSession,
                'commentManagement' => $commentManagement,
                '_request' => $this->resource,
                'settingsProvider' => $this->settingsProvider,
                'logger' => $logger,
                'fileProcessor' => $fileProcessor,
                'messageManager' => $this->messageManager
            ]
        );
    }

    /**
     * Test execute.
     *
     * @return void
     */
    public function testExecute()
    {
        $this->prepareRequestParams();
        $this->prepareSettingsProvider();
        $this->checkoutSession->expects($this->atLeastOnce())->method('clearQuote')->willReturnSelf();

        $this->assertInstanceOf(Json::class, $this->controller->execute());
    }

    /**
     * Test execute with Billing Address.
     *
     * @return void
     */
    public function testExecuteWithBillingAddress()
    {
        $this->prepareRequestParams();
        $this->quote->expects($this->atLeastOnce())->method('removeAddress')->with(5)->willReturn(true);
        $this->quote->expects($this->exactly(3))
            ->method('getBillingAddress')
            ->willReturnOnConsecutiveCalls(true, $this->getAddress(), false, true);
        $this->prepareSettingsProvider();
        $this->checkoutSession->expects($this->atLeastOnce())->method('clearQuote')->willReturnSelf();

        $this->assertInstanceOf(Json::class, $this->controller->execute());
    }

    /**
     * Test execute with Shipping Address.
     *
     * @return void
     */
    public function testExecuteWithShippingAddress()
    {
        $this->prepareRequestParams();
        $this->quote->expects($this->atLeastOnce())->method('removeAddress')->with(5)->willReturn(true);
        $this->quote->expects($this->exactly(3))
            ->method('getShippingAddress')
            ->willReturnOnConsecutiveCalls(true, $this->getAddress(), false, true);
        $this->prepareSettingsProvider();
        $this->checkoutSession->expects($this->atLeastOnce())->method('clearQuote')->willReturnSelf();

        $this->assertInstanceOf(Json::class, $this->controller->execute());
    }

    /**
     * Test execute with Shipping Address.
     *
     * @return void
     */
    public function testExecuteWithExtensionAttributes()
    {
        $this->prepareRequestParams();
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShippingAssignments', 'setShippingAssignments'])
            ->getMockForAbstractClass();
        $extensionAttributes->expects($this->once())->method('getShippingAssignments')->willReturn(true);
        $extensionAttributes->expects($this->once())->method('setShippingAssignments')->willReturn(true);
        $this->quote->expects($this->exactly(3))->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $this->prepareSettingsProvider();
        $this->checkoutSession->expects($this->atLeastOnce())->method('clearQuote')->willReturnSelf();

        $this->assertInstanceOf(Json::class, $this->controller->execute());
    }

    /**
     * Test execute with Exception.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $this->prepareRequestParams();
        $this->prepareSettingsProvider();
        $exception = new \Exception();
        $this->negotiableQuoteManagement->expects($this->once())->method('create')->willThrowException($exception);
        $this->messageManager->expects($this->once())->method('addExceptionMessage');

        $this->assertInstanceOf(Json::class, $this->controller->execute());
    }

    /**
     * Test execute with LocalizedException.
     *
     * @return void
     */
    public function testExecuteWithLocalizedException()
    {
        $this->prepareRequestParams();
        $this->prepareSettingsProvider();
        $phrase = new Phrase(__('Exception'));
        $exception = new LocalizedException($phrase);
        $this->negotiableQuoteManagement->expects($this->once())->method('create')->willThrowException($exception);
        $this->messageManager->expects($this->once())->method('addErrorMessage');

        $this->assertInstanceOf(Json::class, $this->controller->execute());
    }

    /**
     * Get attachment fields.
     *
     * @return array
     */
    private function getAttachmentFields()
    {
        return [
            0 => [
                'name' => 'product.png',
                'type' => 'image/png',
                'tmp_name' => '/tmp/phpN5Ikxl',
                'error' => 0,
                'size' => 20141
            ],
            1 => [
                'name' => 'box1.png',
                'type' => 'image/png',
                'tmp_name' => '/tmp/php8QspRg',
                'error' => 0,
                'size' => 118561
            ]
        ];
    }

    /**
     * Prepare request params.
     *
     * @return void
     */
    private function prepareRequestParams()
    {
        $this->resource->expects($this->exactly(3))
            ->method('getParam')
            ->withConsecutive(['quote-name'], ['quote-message'], ['quote_id'])
            ->willReturnOnConsecutiveCalls('Test Quote', 'Test comment', 1);
    }

    /**
     * Prepare settings provider.
     *
     * @return void
     */
    private function prepareSettingsProvider()
    {
        $resultJson = $this->createPartialMock(Json::class, ['setData']);
        $this->settingsProvider->expects($this->any())->method('retrieveJsonError')->willReturn($resultJson);
        $this->settingsProvider->expects($this->any())->method('retrieveJsonSuccess')->willReturn($resultJson);
    }

    /**
     *  Create address with interface \Magento\Quote\Api\Data\AddressInterface.
     *
     * @return AddressInterface  Cart billing/shipping address.
     */
    private function getAddress()
    {
        $address = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();
        $address->expects($this->once())->method('getId')->willReturn(5);
        return $address;
    }
}
