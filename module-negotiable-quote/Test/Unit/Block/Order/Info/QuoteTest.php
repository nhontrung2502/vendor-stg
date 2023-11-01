<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Block\Order\Info;

use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\NegotiableQuote\Block\Order\Info\Quote;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for \Magento\NegotiableQuote\Block\Order\Info\Quote class.
 */
class QuoteTest extends TestCase
{
    /**
     * @var Quote
     */
    protected $quote;

    /**
     * @var CartInterface|MockObject
     */
    private $quoteMock;

    /**
     * @var int
     */
    protected $quoteId;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    protected $quoteRepository;

    /**
     * @var Registry|MockObject
     */
    private $registry;

    /**
     * @var Order|MockObject
     */
    protected $order;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilder;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->quoteId = 1;
        $this->registry = $this->createPartialMock(
            Registry::class,
            ['registry']
        );
        $this->quoteMock = $this->getMockForAbstractClass(
            CartInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getId', 'getStoreId']
        );
        $this->quoteMock->expects($this->any())->method('getId')->willReturn($this->quoteId);
        $this->quoteMock->expects($this->any())->method('getStoreId')->willReturn(0);
        $this->quoteRepository = $this->getMockForAbstractClass(CartRepositoryInterface::class);
        $this->storeManagerMock = $this->getMockForAbstractClass(
            StoreManagerInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getStore']
        );
        $storeMock = $this->getMockForAbstractClass(StoreInterface::class);
        $this->storeManagerMock->expects($this->any())->method('getStore')->willReturn($storeMock);
        $storeMock->expects($this->any())->method('getCode')->willReturn('');
        $this->urlBuilder = $this->getMockForAbstractClass(UrlInterface::class);
        $this->order = $this->createPartialMock(
            Order::class,
            ['getQuoteId']
        );
        $this->order->expects($this->any())->method('getQuoteId')->willReturn($this->quoteId);
        $this->registry->expects($this->any())->method('registry')->with('current_order')->willReturn($this->order);
        $objectManager = new ObjectManager($this);
        $this->quote = $objectManager->getObject(
            Quote::class,
            [
                'quoteRepository' => $this->quoteRepository,
                'registry' => $this->registry,
                'quote' => $this->quoteMock,
                '_storeManager' => $this->storeManagerMock,
                '_urlBuilder' => $this->urlBuilder
            ]
        );
    }

    /**
     * Test for getViewQuoteUrl.
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
     * Test for getViewQuoteLabel.
     *
     * @return void
     */
    public function testGetViewQuoteLabel()
    {
        $this->assertEquals('#' . $this->order->getQuoteId() . ': ', $this->quote->getViewQuoteLabel());
    }
}
