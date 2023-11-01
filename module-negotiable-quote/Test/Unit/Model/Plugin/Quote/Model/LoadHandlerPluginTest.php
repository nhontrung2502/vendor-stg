<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Plugin\Quote\Model;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Model\NegotiableQuoteAttributeLoader;
use Magento\NegotiableQuote\Model\NegotiableQuoteRepository;
use Magento\NegotiableQuote\Model\Plugin\Quote\Model\LoadHandlerPlugin;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\Quote\Api\Data\CartExtension;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\QuoteRepository\LoadHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Magento\NegotiableQuote\Model\Plugin\Quote\Model\LoadHandlerPlugin class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LoadHandlerPluginTest extends TestCase
{
    /**
     * @var CartExtensionFactory|MockObject
     */
    private $cartExtensionFactory;

    /**
     * @var NegotiableQuoteRepository|MockObject
     */
    private $negotiableQuoteRepository;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var RestrictionInterface|MockObject
     */
    private $restriction;

    /**
     * @var LoadHandlerPlugin
     */
    private $plugin;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->cartExtensionFactory = $this->getMockBuilder(CartExtensionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->restriction = $this->getMockBuilder(
            RestrictionInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->negotiableQuoteRepository = $this->getMockBuilder(
            NegotiableQuoteRepository::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $attributeLoader = $objectManager->getObject(
            NegotiableQuoteAttributeLoader::class,
            [
                'cartExtensionFactory' => $this->cartExtensionFactory,
                'restriction' => $this->restriction,
                'negotiableQuoteRepository' => $this->negotiableQuoteRepository
            ]
        );
        $this->plugin = $objectManager->getObject(
            LoadHandlerPlugin::class,
            [
                'attributeLoader' => $attributeLoader
            ]
        );
    }

    /**
     * Test beforeLoad method.
     *
     * @return void
     */
    public function testBeforeLoad()
    {
        $quoteId = 1;
        $subject = $this->getMockBuilder(LoadHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quote = $this->getMockBuilder(CartInterface::class)
            ->setMethods(
                [
                    'getId',
                    'getExtensionAttributes',
                    'unsetData',
                    'setExtensionAttributes',
                    'setIsActive',
                    'getCustomer',
                    'getCustomerGroupId'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();
        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $cartExtension = $this->getMockBuilder(CartExtension::class)
            ->disableOriginalConstructor()
            ->setMethods(['setNegotiableQuote'])
            ->getMock();
        $quote->expects($this->atLeastOnce())
            ->method('getCustomer')
            ->willReturn($customer);
        $quote->expects($this->atLeastOnce())
            ->method('getCustomerGroupId')
            ->willReturn(1);
        $customer->expects($this->atLeastOnce())
            ->method('getGroupId')
            ->willReturn(2);
        $quote->expects($this->atLeastOnce())
            ->method('getExtensionAttributes')
            ->willReturnOnConsecutiveCalls($extensionAttributes, $extensionAttributes, null, $extensionAttributes);
        $extensionAttributes->expects($this->atLeastOnce())
            ->method('getNegotiableQuote')
            ->willReturnOnConsecutiveCalls(null, $negotiableQuote);
        $quote->expects($this->atLeastOnce())->method('getId')->willReturn($quoteId);
        $this->negotiableQuoteRepository->expects($this->once())
            ->method('getById')
            ->with($quoteId)
            ->willReturn($negotiableQuote);
        $negotiableQuote->expects($this->atLeastOnce())->method('getIsRegularQuote')->willReturn(true);
        $this->restriction->expects($this->once())->method('setQuote')->with($quote)->willReturnSelf();
        $this->cartExtensionFactory->expects($this->once())->method('create')->willReturn($cartExtension);
        $cartExtension->expects($this->once())->method('setNegotiableQuote')->with($negotiableQuote)->willReturnSelf();
        $quote->expects($this->once())->method('setExtensionAttributes')->with($cartExtension)->willReturnSelf();
        $quote->expects($this->once())->method('setIsActive')->with(true)->willReturnSelf();

        $this->assertEquals([$quote], $this->plugin->beforeLoad($subject, $quote));
    }

    /**
     * Test beforeLoad with negotiable quote.
     *
     * @return void
     */
    public function testBeforeLoadWithNegotiableQuote()
    {
        $subject = $this->getMockBuilder(LoadHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();
        $quote->expects($this->atLeastOnce())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $extensionAttributes->expects($this->once())
            ->method('getNegotiableQuote')
            ->willReturn($negotiableQuote);

        $this->assertEquals([$quote], $this->plugin->beforeLoad($subject, $quote));
    }

    /**
     * Test beforeLoad with exception.
     *
     * @return void
     */
    public function testBeforeLoadWithException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $this->expectExceptionMessage('Negotiated quote not found.');
        $quoteId = 1;
        $exception = new \Exception();
        $subject = $this->getMockBuilder(LoadHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();
        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quote->expects($this->atLeastOnce())
            ->method('getExtensionAttributes')
            ->willReturnOnConsecutiveCalls($extensionAttributes, $extensionAttributes, null, $extensionAttributes);
        $extensionAttributes->expects($this->atLeastOnce())
            ->method('getNegotiableQuote')
            ->willReturnOnConsecutiveCalls(null, $negotiableQuote);
        $quote->expects($this->atLeastOnce())->method('getId')->willReturn($quoteId);
        $this->negotiableQuoteRepository->expects($this->once())
            ->method('getById')
            ->with($quoteId)
            ->willThrowException($exception);

        $this->plugin->beforeLoad($subject, $quote);
    }
}
