<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Email;

use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\Email\LinkBuilder;
use Magento\NegotiableQuote\Model\Email\Provider\SalesRepresentative;
use Magento\NegotiableQuote\Model\Email\RecipientFactory;
use Magento\NegotiableQuote\Model\Email\Sender;
use Magento\NegotiableQuote\Model\NegotiableQuote;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\User\Api\Data\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit test for Sender.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SenderTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var TransportBuilder|MockObject
     */
    private $transportBuilder;

    /**
     * @var RecipientFactory|MockObject
     */
    private $recipientFactory;

    /**
     * @var LinkBuilder|MockObject
     */
    private $linkBuilder;

    /**
     * @var SalesRepresentative|MockObject
     */
    private $salesRepresentativeProvider;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var DataObject|MockObject
     */
    private $emailData;

    /**
     * @var CartInterface|MockObject
     */
    private $quoteMock;

    /**
     * @var Sender
     */
    private $sender;

    /**
     * Set up.
     *
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'getName'])
            ->getMockForAbstractClass();
        $user->expects($this->any())->method('load')->willReturnSelf();
        $user->expects($this->any())->method('getName')->willReturn('Name');
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $transport = $this->getMockBuilder(TransportInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['sendMessage'])
            ->getMockForAbstractClass();
        $this->transportBuilder = $this->getMockBuilder(TransportBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'setTemplateIdentifier',
                    'setTemplateOptions',
                    'setTemplateVars',
                    'setFrom',
                    'setReplyTo',
                    'addTo',
                    'addBcc',
                    'getTransport'
                ]
            )
            ->getMock();
        $this->transportBuilder->expects($this->any())->method('setTemplateIdentifier')->willReturnSelf();
        $this->transportBuilder->expects($this->any())->method('setTemplateOptions')->willReturnSelf();
        $this->transportBuilder->expects($this->any())->method('setTemplateVars')->willReturnSelf();
        $this->transportBuilder->expects($this->any())->method('setFrom')->willReturnSelf();
        $this->transportBuilder->expects($this->any())->method('setReplyTo')->willReturnSelf();
        $this->transportBuilder->expects($this->any())->method('addTo')->willReturnSelf();
        $this->transportBuilder->expects($this->any())->method('addBcc')->willReturnSelf();
        $this->transportBuilder->expects($this->any())->method('addBcc')->willReturnSelf();
        $this->transportBuilder->expects($this->any())->method('getTransport')->willReturn($transport);
        $this->recipientFactory = $this->getMockBuilder(RecipientFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['createForQuote'])
            ->getMock();
        $this->linkBuilder = $this->getMockBuilder(LinkBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->salesRepresentativeProvider = $this
            ->getMockBuilder(SalesRepresentative::class)
            ->disableOriginalConstructor()
            ->getMock();
        $transport->expects($this->any())->method('sendMessage')->willReturnSelf();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->emailData = null;
        $this->quoteMock = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomer', 'getExtensionAttributes'])
            ->getMockForAbstractClass();
        $objectManager = new ObjectManager($this);
        $this->sender = $objectManager->getObject(
            Sender::class,
            [
                'scopeConfig' => $this->scopeConfig,
                'storeManager' => $this->storeManager,
                'transportBuilder' => $this->transportBuilder,
                'recipientFactory' => $this->recipientFactory,
                'linkBuilder' => $this->linkBuilder,
                'salesRepresentativeProvider' => $this->salesRepresentativeProvider,
                'logger' => $this->logger,
                'emailData' => $this->emailData,
            ]
        );
    }

    /**
     * Test for method sendChangeQuoteEmailToMerchant.
     *
     * @return void
     */
    public function testSendChangeQuoteEmailToMerchant()
    {
        $emailTemplate = 'Email template';
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreId', 'getId'])
            ->getMockForAbstractClass();
        $customer->expects($this->any())->method('getStoreId')->willReturn(1);
        $customer->expects($this->any())->method('getId')->willReturn(14);
        $company = $this->getMockForAbstractClass(CompanyInterface::class);
        $company->expects($this->any())->method('getSalesRepresentativeId')->willReturn(14);
        $this->quoteMock->expects($this->any())->method('getCustomer')
            ->willReturn($customer);
        $quoteNegotiation = $this->createMock(NegotiableQuote::class);
        $quoteNegotiation->expects($this->any())->method('getQuoteName')->willReturn('');
        $extensionAttributes = $this->getMockForAbstractClass(
            CartExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getNegotiableQuote']
        );
        $extensionAttributes->expects($this->any())->method('getNegotiableQuote')
            ->willReturn($quoteNegotiation);
        $this->quoteMock->expects($this->any())->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        $this->emailData = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreId'])
            ->getMock();
        $this->emailData->expects($this->any())->method('getStoreId')
            ->willReturn(1);
        $this->recipientFactory->expects($this->atLeastOnce())->method('createForQuote')->willReturn($this->emailData);
        $this->scopeConfig->expects($this->any())->method('getValue')
            ->willReturn(true);

        $this->sender->sendChangeQuoteEmailToMerchant($this->quoteMock, $emailTemplate);
    }

    /**
     * Test sendChangeQuoteEmailToMerchant with Exception.
     *
     * @return void
     */
    public function testSendChangeQuoteEmailToMerchantWithException()
    {
        $emailTemplate = 'Email template';
        $exception = new \Exception();
        $this->emailData = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreId'])
            ->getMock();
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->emailData->expects($this->any())->method('getStoreId')->willThrowException($exception);
        $this->recipientFactory->expects($this->atLeastOnce())->method('createForQuote')->willReturn($this->emailData);
        $this->quoteMock->expects($this->any())->method('getCustomer')->willReturn($customer);
        $this->logger->expects($this->once())->method('critical');

        $this->sender->sendChangeQuoteEmailToMerchant($this->quoteMock, $emailTemplate);
    }

    /**
     * Test for method sendChangeQuoteEmailToBuyer.
     *
     * @param int $storeId
     * @return void
     * @dataProvider dataProviderSendChangeQuoteEmailToBuyer
     */
    public function testSendChangeQuoteEmailToBuyer($storeId)
    {
        $emailTemplate = 'Email template';
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customer->expects($this->any())->method('getStoreId')->willReturn($storeId);
        $customer->expects($this->any())->method('getId')->willReturn(14);
        $this->quoteMock->expects($this->any())->method('getCustomer')
            ->willReturn($customer);
        $this->emailData = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreId'])
            ->getMock();
        $this->emailData->expects($this->any())->method('getStoreId')
            ->willReturn(1);
        $this->recipientFactory->expects($this->atLeastOnce())->method('createForQuote')->willReturn($this->emailData);
        $this->scopeConfig->expects($this->any())->method('getValue')
            ->willReturn(true);
        $store = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $store->expects($this->any())->method('getCode')->willReturn(1);
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($store);

        $this->sender->sendChangeQuoteEmailToBuyer($this->quoteMock, $emailTemplate);
    }

    /**
     * Test sendChangeQuoteEmailToBuyer with Exception.
     *
     * @return void
     */
    public function testSendChangeQuoteEmailToBuyerWithException()
    {
        $emailTemplate = 'Email template';
        $exception = new \Exception();
        $this->emailData = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreId'])
            ->getMock();
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->emailData->expects($this->any())->method('getStoreId')->willThrowException($exception);
        $this->quoteMock->expects($this->any())->method('getCustomer')->willReturn($customer);
        $this->recipientFactory->expects($this->atLeastOnce())->method('createForQuote')->willReturn($this->emailData);
        $this->logger->expects($this->once())->method('critical');

        $this->sender->sendChangeQuoteEmailToBuyer($this->quoteMock, $emailTemplate);
    }

    /**
     * DataProvider SendChangeQuoteEmailToBuyer.
     *
     * @return array
     */
    public function dataProviderSendChangeQuoteEmailToBuyer()
    {
        return [
            [1],
            [0]
        ];
    }
}
