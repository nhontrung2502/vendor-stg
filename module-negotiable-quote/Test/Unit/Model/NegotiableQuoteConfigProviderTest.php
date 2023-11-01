<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model;

use Magento\Checkout\Model\Session;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Model\NegotiableQuote;
use Magento\NegotiableQuote\Model\NegotiableQuoteConfigProvider;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for NegotiableQuoteConfigProvider.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class NegotiableQuoteConfigProviderTest extends TestCase
{
    /**
     * @var NegotiableQuoteConfigProvider
     */
    private $negotiableQuoteConfigProvider;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var NegotiableQuoteRepositoryInterface|MockObject
     */
    private $negotiableQuoteRepository;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var AddressRepositoryInterface|MockObject
     */
    private $addressRepository;

    /**
     * @var Session|MockObject
     */
    private $session;

    /**
     * @var AddressInterface|MockObject
     */
    private $address;

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
        $objectManager = new ObjectManager($this);
        $this->context = $objectManager->getObject(Context::class);

        $this->negotiableQuoteRepository = $this->getMockForAbstractClass(
            NegotiableQuoteRepositoryInterface::class,
            [],
            '',
            false
        );
        $this->quoteRepository = $this->getMockForAbstractClass(
            CartRepositoryInterface::class,
            ['get'],
            '',
            false
        );
        $this->addressRepository = $this->getMockForAbstractClass(
            AddressRepositoryInterface::class,
            [],
            '',
            false
        );
        $this->session = $this->createMock(Session::class);

        $this->address = $this->getMockForAbstractClass(
            AddressInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getCustomerAddressId', 'getShippingMethod', 'getData']
        );

        $this->quote = $this->getMockForAbstractClass(
            CartInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getShippingAddress', 'getPayment', 'getExtensionAttributes']
        );

        $this->negotiableQuoteConfigProvider = $objectManager->getObject(
            NegotiableQuoteConfigProvider::class,
            [
                'context' => $this->context,
                'negotiableQuoteRepository' => $this->negotiableQuoteRepository,
                'quoteRepository' => $this->quoteRepository,
                'addressRepository' => $this->addressRepository,
                'session' => $this->session,
            ]
        );
    }

    /**
     * Test for method getConfig.
     *
     * @return void
     */
    public function testGetConfig()
    {
        $quoteValue = 42;
        $this->session->expects($this->any())->method('getQuoteId')->willReturn($quoteValue);

        $orderPayment = $this->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMethod'])
            ->getMockForAbstractClass();

        $this->quoteRepository->expects($this->any())->method('get')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturn($this->address);
        $this->address->expects($this->any())->method('getCustomerAddressId')->willReturn($quoteValue);
        $this->addressRepository->expects($this->any())->method('getById')
            ->with($quoteValue)->willReturn($this->address);
        $this->quote->expects($this->any())->method('getPayment')->willReturn($orderPayment);
        $orderPayment->expects($this->any())->method('getMethod')->willReturn($quoteValue);

        $this->assertArrayHasKey('isNegotiableQuote', $this->negotiableQuoteConfigProvider->getConfig());
    }

    /**
     * Test for method getConfig without quoteId.
     *
     * @return void
     */
    public function testGetConfigNoQuoteId()
    {
        $quoteValue = 42;
        $this->session->expects($this->any())->method('getQuoteId')->willReturn(null);

        $orderPayment = $this->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMethod'])
            ->getMockForAbstractClass();

        $this->quoteRepository->expects($this->any())->method('get')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturn($this->address);
        $this->address->expects($this->any())->method('getCustomerAddressId')->willReturn($quoteValue);
        $this->addressRepository->expects($this->any())->method('getById')
            ->with($quoteValue)->willReturn($this->address);
        $this->quote->expects($this->any())->method('getPayment')->willReturn($orderPayment);
        $orderPayment->expects($this->any())->method('getMethod')->willReturn($quoteValue);

        $this->assertArrayHasKey('isNegotiableQuote', $this->negotiableQuoteConfigProvider->getConfig());
    }

    /**
     * Test for method getConfig catching NoSuchEntityException.
     *
     * @return void
     */
    public function testGetConfigWithException()
    {
        $exception = new NoSuchEntityException();
        $this->quoteRepository->expects($this->any())->method('get')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturn($this->address);
        $this->address->expects($this->any())->method('getCustomerAddressId')->willThrowException($exception);
        $this->address->expects($this->any())->method('getData')->willReturn([]);

        $this->assertArrayHasKey('isNegotiableQuote', $this->negotiableQuoteConfigProvider->getConfig());
    }

    /**
     * Test for method getConfig with context mock.
     *
     * @return void
     */
    public function testGetConfigWithContext()
    {
        $quoteValue = 42;
        $this->context->getRequest()->expects($this->any())->method('getParam')
            ->with('negotiableQuoteId')->willReturn($quoteValue);
        $this->quoteRepository->expects($this->any())->method('get')->willReturn($this->quote);

        $orderPayment = $this->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMethod'])
            ->getMockForAbstractClass();

        $this->quote->expects($this->any())->method('getPayment')->willReturn($orderPayment);
        $orderPayment->expects($this->any())->method('getMethod')->willReturn($quoteValue);

        $extensionAttributes = $this->getMockForAbstractClass(
            CartExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getNegotiableQuote']
        );
        $this->quote->expects($this->any())->method('getExtensionAttributes')->willReturn($extensionAttributes);

        $quoteNegotiation = $this->createMock(NegotiableQuote::class);
        $extensionAttributes->expects($this->any())->method('getNegotiableQuote')->willReturn($quoteNegotiation);
        $quoteNegotiation->expects($this->once())->method('getStatus')->willReturn($quoteValue);
        $quoteNegotiation->expects($this->once())->method('getNegotiatedPriceValue')->willReturn($quoteValue);

        $this->assertArrayHasKey('isNegotiableQuote', $this->negotiableQuoteConfigProvider->getConfig());
    }
}
