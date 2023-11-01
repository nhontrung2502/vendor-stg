<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Quote;

use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Controller\Quote\CheckDiscount;
use Magento\NegotiableQuote\Model\SettingsProvider;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test for Magento\NegotiableQuote\Controller\Quote\CheckDiscount class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CheckDiscountTest extends TestCase
{
    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var Http|MockObject
     */
    private $request;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var SettingsProvider|MockObject
     */
    private $settingsProvider;

    /**
     * @var Json|MockObject
     */
    private $serializer;

    /**
     * @var CartInterface|MockObject
     */
    private $quote;

    /**
     * @var \Magento\Framework\Controller\Result\Json|MockObject
     */
    private $json;

    /**
     * @var CheckDiscount
     */
    private $checkDiscount;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->settingsProvider = $this->getMockBuilder(SettingsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->serializer = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getGiftCards', 'getCouponCode'])
            ->getMockForAbstractClass();
        $this->json = $this->getMockBuilder(\Magento\Framework\Controller\Result\Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->checkDiscount = $objectManager->getObject(
            CheckDiscount::class,
            [
                'quoteRepository' => $this->quoteRepository,
                'logger' => $this->logger,
                'settingsProvider' => $this->settingsProvider,
                'messageManager' => $this->messageManager,
                '_request' => $this->request,
                'serializer' => $this->serializer,
            ]
        );
    }

    /**
     * Test execute method.
     *
     * @return void
     */
    public function testSuccessExecute()
    {
        $quoteId = 1;
        $this->request->expects($this->once())->method('getParam')->with('quote_id')->willReturn($quoteId);
        $this->settingsProvider->expects($this->once())
            ->method('retrieveJsonError')
            ->willReturn($this->json);
        $this->quoteRepository->expects($this->once())->method('get')->willReturn($this->quote);
        $this->quote->expects($this->once())->method('getGiftCards')->willReturn(null);
        $this->serializer->expects($this->never())
            ->method('unserialize');
        $this->quote->expects($this->once())->method('getCouponCode')->willReturn('132fdw43f234');
        $this->settingsProvider->expects($this->once())
            ->method('retrieveJsonSuccess')
            ->with(['discount' => true])
            ->willReturn($this->json);

        $this->assertSame($this->json, $this->checkDiscount->execute());
    }

    /**
     * Test execute without quote id.
     *
     * @return void
     */
    public function testExecuteWithError()
    {
        $this->request->expects($this->once())->method('getParam')->with('quote_id')->willReturn(false);
        $this->settingsProvider->expects($this->once())->method('retrieveJsonError')->willReturn($this->json);

        $this->assertSame($this->json, $this->checkDiscount->execute());
    }

    /**
     * Test execute without coupon and without gift card.
     *
     * @return void
     */
    public function testExecuteWithoutCoupon()
    {
        $quoteId = 1;
        $this->request->expects($this->once())->method('getParam')->with('quote_id')->willReturn($quoteId);
        $this->settingsProvider->expects($this->once())
            ->method('retrieveJsonError')
            ->willReturn($this->json);
        $this->quoteRepository->expects($this->once())->method('get')->willReturn($this->quote);
        $this->quote->expects($this->once())->method('getGiftCards')->willReturn(null);
        $this->quote->expects($this->once())->method('getCouponCode')->willReturn(null);

        $this->assertSame($this->json, $this->checkDiscount->execute());
    }

    /**
     * Test execute with gift card and without coupon.
     *
     * @return void
     */
    public function testExecuteWithoutCouponAndWithGiftCard()
    {
        $quoteId = 1;
        $giftCard = [
            'i' => '2',
            'c' => '0069H38J54IG',
            'a' => 10,
            'ba' => '10.0000',
        ];
        $this->request->expects($this->once())->method('getParam')->with('quote_id')->willReturn($quoteId);
        $this->settingsProvider->expects($this->once())
            ->method('retrieveJsonError')
            ->willReturn($this->json);
        $this->quoteRepository->expects($this->once())->method('get')->willReturn($this->quote);
        $this->quote->expects($this->any())
            ->method('getGiftCards')
            ->willReturn('[{"i":"2","c":"0069H38J54IG","a":10,"ba":"10.0000"}]');
        $this->serializer->expects($this->once())->method('unserialize')->willReturn($giftCard);
        $this->quote->expects($this->never())->method('getCouponCode');
        $this->settingsProvider->expects($this->once())
            ->method('retrieveJsonSuccess')
            ->with(['discount' => true])
            ->willReturn($this->json);

        $this->assertSame($this->json, $this->checkDiscount->execute());
    }

    /**
     * Test execute with LocalizedException.
     *
     * @return void
     */
    public function testExecuteWithLocalizedException()
    {
        $quoteId = 1;
        $exceptionMessage = 'No such entity with cartId = 1';
        $exception = new LocalizedException(__($exceptionMessage));
        $this->request->expects($this->once())->method('getParam')->with('quote_id')->willReturn($quoteId);
        $this->settingsProvider->expects($this->once())
            ->method('retrieveJsonError')
            ->willReturn($this->json);
        $this->quoteRepository->expects($this->once())->method('get')->with($quoteId)->willThrowException($exception);
        $this->messageManager->expects($this->once())
            ->method('addError')
            ->with($exceptionMessage)
            ->willReturnSelf();
        $this->logger->expects($this->once())->method('critical')->with($exception)->willReturnSelf();

        $this->assertSame($this->json, $this->checkDiscount->execute());
    }

    /**
     * Test execute with Exception.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $quoteId = 1;
        $exceptionMessage = 'An error occurred while quote creation.';
        $exception = new \Exception($exceptionMessage);
        $this->request->expects($this->once())->method('getParam')->with('quote_id')->willReturn($quoteId);
        $this->settingsProvider->expects($this->once())
            ->method('retrieveJsonError')
            ->willReturn($this->json);
        $this->quoteRepository->expects($this->once())->method('get')->with($quoteId)->willThrowException($exception);
        $this->messageManager->expects($this->once())
            ->method('addException')
            ->with($exception, $exceptionMessage)
            ->willReturnSelf();
        $this->logger->expects($this->once())->method('critical')->with($exception)->willReturnSelf();

        $this->assertSame($this->json, $this->checkDiscount->execute());
    }
}
