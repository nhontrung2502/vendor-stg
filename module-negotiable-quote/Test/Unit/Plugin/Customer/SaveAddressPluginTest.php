<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Plugin\Customer;

use Magento\Company\Api\AuthorizationInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\Quote\Address;
use Magento\NegotiableQuote\Plugin\Customer\SaveAddressPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit test for Magento\NegotiableQuote\Plugin\Customer\SaveAddressPlugin class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveAddressPluginTest extends TestCase
{
    /**
     * @var Context|MockObject
     */
    private $context;

    /**
     * @var Address|MockObject
     */
    private $negotiableQuoteAddress;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var AuthorizationInterface|MockObject
     */
    private $authorization;

    /**
     * @var SaveAddressPlugin
     */
    private $plugin;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->negotiableQuoteAddress = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->authorization = $this->getMockBuilder(AuthorizationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->plugin = $objectManager->getObject(
            SaveAddressPlugin::class,
            [
                'context' => $this->context,
                'negotiableQuoteAddress' => $this->negotiableQuoteAddress,
                'logger' => $this->logger,
                'authorization' => $this->authorization,
            ]
        );
    }

    /**
     * Test aroundSave method.
     *
     * @return void
     */
    public function testAfterSave()
    {
        $quoteId = 1;
        $subject = $this->getMockBuilder(AddressRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $address = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->context->expects($this->once())->method('getRequest')->willReturn($request);
        $request->expects($this->once())
            ->method('getParam')
            ->with('quoteId')
            ->willReturn($quoteId);
        $this->negotiableQuoteAddress->expects($this->once())
            ->method('updateQuoteShippingAddress')
            ->with($quoteId, $address)
            ->willReturn(true);
        $this->authorization->expects($this->atLeastOnce())
            ->method('isAllowed')
            ->with('Magento_NegotiableQuote::manage')
            ->willReturn(true);

        $this->assertEquals($address, $this->plugin->afterSave($subject, $address));
    }

    /**
     * Test aroundSave method without quote id.
     *
     * @return void
     */
    public function testAfterSaveWithoutQuoteId()
    {
        $subject = $this->getMockBuilder(AddressRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $address = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);
        $request->expects($this->once())
            ->method('getParam')
            ->with('quoteId')
            ->willReturn(null);

        $this->assertEquals($address, $this->plugin->afterSave($subject, $address));
    }

    /**
     * Test aroundSave method with NoSuchEntityException.
     *
     * @return void
     */
    public function testAfterSaveWithNoSuchEntityException()
    {
        $quoteId = 1;
        $subject = $this->getMockBuilder(AddressRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $address = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $exception = new NoSuchEntityException(__('No such entity.'));
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);
        $request->expects($this->once())
            ->method('getParam')
            ->with('quoteId')
            ->willReturn($quoteId);
        $this->negotiableQuoteAddress->expects($this->once())
            ->method('updateQuoteShippingAddress')
            ->with($quoteId, $address)
            ->willThrowException($exception);
        $this->context->expects($this->once())
            ->method('getMessageManager')
            ->willReturn($messageManager);
        $messageManager->expects($this->once())
            ->method('addErrorMessage')
            ->with(__('Requested quote was not found'))
            ->willReturnSelf();
        $this->authorization->expects($this->atLeastOnce())
            ->method('isAllowed')
            ->with('Magento_NegotiableQuote::manage')
            ->willReturn(true);

        $this->assertEquals($address, $this->plugin->afterSave($subject, $address));
    }

    /**
     * Test aroundSave method with Exception.
     *
     * @return void
     */
    public function testAfterSaveWithException()
    {
        $quoteId = 1;
        $subject = $this->getMockBuilder(AddressRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $address = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $exception = new \Exception();
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);
        $request->expects($this->once())
            ->method('getParam')
            ->with('quoteId')
            ->willReturn($quoteId);
        $this->negotiableQuoteAddress->expects($this->once())
            ->method('updateQuoteShippingAddress')
            ->with($quoteId, $address)
            ->willThrowException($exception);
        $this->logger->expects($this->once())
            ->method('critical')
            ->with($exception);
        $this->context->expects($this->once())
            ->method('getMessageManager')
            ->willReturn($messageManager);
        $messageManager->expects($this->once())
            ->method('addErrorMessage')
            ->with(__('Unable to update shipping address'))
            ->willReturnSelf();
        $this->authorization->expects($this->atLeastOnce())
            ->method('isAllowed')
            ->with('Magento_NegotiableQuote::manage')
            ->willReturn(true);

        $this->assertEquals($address, $this->plugin->afterSave($subject, $address));
    }

    /**
     * Test aroundSave method without quote id.
     *
     * @return void
     */
    public function testAfterSaveWithoutPermissions()
    {
        $quoteId = 1;
        $subject = $this->getMockBuilder(AddressRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $address = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);
        $request->expects($this->once())
            ->method('getParam')
            ->with('quoteId')
            ->willReturn($quoteId);
        $this->authorization->expects($this->atLeastOnce())
            ->method('isAllowed')
            ->with('Magento_NegotiableQuote::manage')
            ->willReturn(false);

        $this->assertEquals($address, $this->plugin->afterSave($subject, $address));
    }
}
