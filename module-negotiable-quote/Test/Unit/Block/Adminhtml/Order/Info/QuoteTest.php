<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Block\Adminhtml\Order\Info;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\NegotiableQuote\Block\Adminhtml\Order\Info\Quote;
use Magento\NegotiableQuote\Model\NegotiableQuote;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QuoteTest extends TestCase
{
    /**
     * @var \Magento\NegotiableQuote\Block\Adminhtml\Order\Info\Quote
     */
    private $quote;

    /**
     * @var int
     */
    private $quoteId;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var Order|MockObject
     */
    private $order;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilder;

    /**
     * @var Registry|MockObject
     */
    private $registry;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->quoteId = 1;
        $this->quoteRepository = $this->getMockForAbstractClass(CartRepositoryInterface::class);
        $this->urlBuilder = $this->getMockForAbstractClass(UrlInterface::class);
        $this->order = $this->createPartialMock(
            Order::class,
            ['getQuoteId']
        );
        $this->registry = $this->createPartialMock(
            Registry::class,
            ['registry']
        );
        $this->registry->expects($this->any())->method('registry')->with('current_order')->willReturn($this->order);
        $this->order->expects($this->any())->method('getQuoteId')->willReturn($this->quoteId);
        $objectManager = new ObjectManager($this);
        $this->quote = $objectManager->getObject(
            Quote::class,
            [
                'quoteRepository' => $this->quoteRepository,
                'registry' => $this->registry,
                '_urlBuilder' => $this->urlBuilder
            ]
        );
    }

    /**
     * Test getViewQuoteUrl
     *
     * @return void
     */
    public function testGetViewQuoteUrl()
    {
        $path = 'quotes/quote/view/';
        $url = 'http://example.com/';

        $this->urlBuilder->expects($this->any())
            ->method('getUrl')->willReturn(
                $url . $path . '/quote_id/' . $this->quoteId . '/'
            );

        $this->assertEquals($url . $path . '/quote_id/1/', $this->quote->getViewQuoteUrl());
    }

    /**
     * Test getViewQuoteLabel
     *
     * @return void
     */
    public function testGetViewQuoteLabel()
    {
        $this->assertEquals('#' . $this->order->getQuoteId() . ': ', $this->quote->getViewQuoteLabel());
    }

    /**
     * Test getQuoteName
     *
     * @return void
     */
    public function testGetQuoteName()
    {
        $quoteName = 'Test Quote';
        $quoteNegotiation = $this->getQuoteNegotiationMock();
        $quoteNegotiation->expects($this->exactly(2))->method('getQuoteName')->willReturn($quoteName);

        $this->assertSame($quoteName, $this->quote->getQuoteName());
    }

    /**
     * Test getQuoteName with exception
     *
     * @return void
     */
    public function testGetQuoteNameWithException()
    {
        $this->quoteRepository->expects($this->once())
            ->method('get')
            ->willThrowException(new NoSuchEntityException());
        $this->assertNull($this->quote->getQuoteName());
    }

    /**
     * Test isNegotiableQuote
     *
     * @param bool $expectedResult
     * @param int $quoteId
     * @dataProvider isNegotiableQuoteDataProvider
     */
    public function testIsNegotiableQuote($expectedResult, $quoteId)
    {
        $quoteNegotiation = $this->getQuoteNegotiationMock();
        $quoteNegotiation->expects($this->once())->method('getQuoteId')->willReturn($quoteId);

        $this->assertEquals($expectedResult, $this->quote->isNegotiableQuote());
    }

    /**
     * @return NegotiableQuote
     */
    private function getQuoteNegotiationMock()
    {
        $cart = $this->createMock(
            CartInterface::class
        );
        $cartExtensionAttributes = $this->getMockForAbstractClass(
            CartExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getQuoteName', 'getNegotiableQuote']
        );
        $quoteNegotiation = $this->createMock(
            NegotiableQuote::class
        );
        $this->quoteRepository->expects($this->once())->method('get')->willReturn($cart);
        $cart->expects($this->once())->method('getExtensionAttributes')->willReturn($cartExtensionAttributes);
        $cartExtensionAttributes->expects($this->any())->method('getNegotiableQuote')->willReturn($quoteNegotiation);

        return $quoteNegotiation;
    }

    /**
     * @return array
     */
    public function isNegotiableQuoteDataProvider()
    {
        return [
            [true, 1],
            [false, null]
        ];
    }
}
