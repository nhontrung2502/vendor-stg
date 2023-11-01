<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Quote;

use Exception;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\Manager;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Controller\Quote\UpdateAddress;
use Magento\NegotiableQuote\Helper\Quote;
use Magento\NegotiableQuote\Model\Quote\Address;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\NegotiableQuote\Model\SettingsProvider;
use Magento\Quote\Api\CartRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateAddressTest extends TestCase
{
    /**
     * @var Quote|MockObject
     */
    private $quoteHelper;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var RestrictionInterface|MockObject
     */
    private $customerRestriction;

    /**
     * @var NegotiableQuoteManagementInterface|MockObject
     */
    private $negotiableQuoteManagement;

    /**
     * @var UpdateAddress
     */
    private $updateAddress;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var SettingsProvider|MockObject
     */
    private $settingsProvider;

    /**
     * @var Manager|MockObject
     */
    private $messageManager;

    /**
     * @var Json|MockObject
     */
    private $resultJson;

    /**
     * @var Address|MockObject
     */
    private $negotiableQuoteAddress;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->request = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['isPost'])
            ->getMockForAbstractClass();
        $this->resultJson = $this->createPartialMock(
            Json::class,
            ['setData']
        );
        $this->settingsProvider = $this->createPartialMock(
            SettingsProvider::class,
            ['retrieveJsonError', 'retrieveJsonSuccess']
        );
        $this->messageManager = $this->createMock(Manager::class);
        $this->quoteHelper = $this->createMock(Quote::class);
        $this->quoteRepository = $this->getMockForAbstractClass(CartRepositoryInterface::class);
        $this->customerRestriction = $this->createMock(
            RestrictionInterface::class
        );
        $this->negotiableQuoteManagement = $this->createMock(
            NegotiableQuoteManagementInterface::class
        );
        $this->negotiableQuoteAddress = $this->createMock(
            Address::class
        );
        $objectManager = new ObjectManager($this);
        $this->updateAddress = $objectManager->getObject(
            UpdateAddress::class,
            [
                'request' => $this->request,
                'messageManager' => $this->messageManager,
                'quoteHelper' => $this->quoteHelper,
                'quoteRepository' => $this->quoteRepository,
                'customerRestriction' => $this->customerRestriction,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagement,
                'settingsProvider' => $this->settingsProvider,
                'negotiableQuoteAddress' => $this->negotiableQuoteAddress
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
        $this->request->expects($this->atLeastOnce())->method('isPost')->willReturn(true);
        $this->request->method('getParam')
            ->withConsecutive(['quote_id'], ['address_id'])
            ->willReturnOnConsecutiveCalls(1, 1);
        $quote = $this->createPartialMock(
            \Magento\Quote\Model\Quote::class,
            ['getExtensionAttributes', 'getShippingAddress']
        );
        $this->quoteRepository->expects($this->once())->method('get')->with(1)->willReturn($quote);
        $this->customerRestriction->expects($this->once())->method('canSubmit')->willReturn(true);
        $this->negotiableQuoteAddress->expects($this->once())->method('updateAddress')->with(1, 1);
        $this->settingsProvider->expects($this->once())->method('retrieveJsonSuccess')->willReturn($this->resultJson);

        $this->assertInstanceOf(Json::class, $this->updateAddress->execute());
    }

    /**
     * Test for method execute not POST.
     *
     * @return void
     */
    public function testExecuteNotIsPost(): void
    {
        $this->request->expects($this->atLeastOnce())->method('isPost')->willReturn(false);
        $this->messageManager->expects($this->once())->method('addErrorMessage');
        $this->settingsProvider->expects($this->once())->method('retrieveJsonError')->willReturn($this->resultJson);

        $this->assertInstanceOf(Json::class, $this->updateAddress->execute());
    }

    /**
     * Test execute with no such entity exception.
     *
     * @return void
     */
    public function testExecuteWithNoSuchEntityException(): void
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $exception = new NoSuchEntityException();
        $phrase = new Phrase('Requested quote was not found');
        $this->request->expects($this->atLeastOnce())->method('isPost')->willReturn(true);
        $this->request->method('getParam')
            ->withConsecutive(['quote_id'], ['address_id'])
            ->willReturnOnConsecutiveCalls(1, 1);
        $quote = $this->createMock(
            \Magento\Quote\Model\Quote::class
        );
        $this->quoteRepository->expects($this->once())->method('get')->with(1)->willReturn($quote);
        $this->customerRestriction->expects($this->once())->method('canSubmit')->willReturn(false);
        $this->settingsProvider->expects($this->once())->method('retrieveJsonSuccess')->willThrowException($exception);
        $this->messageManager->expects($this->once())->method('addErrorMessage')->with($phrase);
        $this->settingsProvider->expects($this->once())->method('retrieveJsonError')->willThrowException($exception);

        $this->assertInstanceOf(Json::class, $this->updateAddress->execute());
    }

    /**
     * Test execute with exception.
     *
     * @return void
     */
    public function testExecuteWithException(): void
    {
        $this->expectException('Exception');
        $exception = new Exception();
        $phrase = new Phrase('Something went wrong. Please try again later.');
        $this->request->expects($this->atLeastOnce())->method('isPost')->willReturn(true);
        $this->request->method('getParam')
            ->withConsecutive(['quote_id'], ['address_id'])
            ->willReturnOnConsecutiveCalls(1, 1);
        $quote = $this->createMock(
            \Magento\Quote\Model\Quote::class
        );
        $this->quoteRepository->expects($this->once())->method('get')->with(1)->willReturn($quote);
        $this->customerRestriction->expects($this->once())->method('canSubmit')->willReturn(false);
        $this->settingsProvider->expects($this->once())->method('retrieveJsonSuccess')->willThrowException($exception);
        $this->messageManager->expects($this->once())->method('addErrorMessage')->with($phrase);
        $this->settingsProvider->expects($this->once())->method('retrieveJsonError')->willThrowException($exception);

        $this->assertInstanceOf(Json::class, $this->updateAddress->execute());
    }
}
