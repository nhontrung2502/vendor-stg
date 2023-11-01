<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Plugin\Sales\Controller\Order;

use Magento\Checkout\Model\Cart;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Plugin\Sales\Controller\Order\ReorderPlugin;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\AbstractController\OrderLoaderInterface;
use Magento\Sales\Helper\Reorder;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Model\ProductRepository;

/**
 * Unit test for Plugin/Sales/Controller/Order/ReorderPlugin model.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReorderPluginTest extends TestCase
{
    /**
     * @var Cart|MockObject
     */
    private $cart;

    /**
     * @var OrderLoaderInterface|MockObject
     */
    private $orderLoader;

    /**
     * @var OrderRepositoryInterface|MockObject
     */
    private $orderRepository;

    /**
     * @var AddressRepositoryInterface|MockObject
     */
    private $addressRepository;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var Redirect|MockObject
     */
    private $resultFactory;

    /**
     * @var \Magento\Sales\Controller\Order\Reorder|MockObject
     */
    private $subject;

    /**
     * @var Order|MockObject
     */
    private $order;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var ReorderPlugin
     */
    private $reorderPlugin;

    /**
     * @var Reorder|MockObject
     */
    private $reorder;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     *      |\PHPUnit\Framework\MockObject\MockObject
     */
    private $productCollectionFactory;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->cart = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderLoader = $this->getMockBuilder(OrderLoaderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->orderRepository = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->addressRepository = $this->getMockBuilder(AddressRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->subject = $this->getMockBuilder(\Magento\Sales\Controller\Order\Reorder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->reorder = $this->getMockBuilder(Reorder::class)
            ->disableOriginalConstructor()
            ->setMethods(['canReorder'])
            ->getMock();
        $objectManager = new ObjectManager($this);
        $this->productCollectionFactory = $this->getMockBuilder(
            \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class
        )
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $objectManager = new ObjectManager($this);
        $this->reorderPlugin = $objectManager->getObject(
            ReorderPlugin::class,
            [
                'messageManager' => $this->messageManager,
                'resultFactory' => $this->resultFactory,
                'cart' => $this->cart,
                'orderLoader' => $this->orderLoader,
                'orderRepository' => $this->orderRepository,
                'addressRepository' => $this->addressRepository,
                'logger' => $this->logger,
                'reorder' => $this->reorder,
                'productCollectionFactory' => $this->productCollectionFactory,
            ]
        );
    }

    /**
     * Test aroundExecute.
     *
     * @return void
     */
    public function testAroundExecute()
    {
        $exceptionMessage = 'test';
        $exception = new \Exception($exceptionMessage);
        $this->cart->expects($this->any())->method('addOrderItem')->willThrowException($exception);
        $this->aroundExecute(true);

        $proceed = function () {
            return true;
        };

        $this->assertInstanceOf(
            ResultInterface::class,
            $this->reorderPlugin->aroundExecute($this->subject, $proceed)
        );
    }

    /**
     * Test aroundExecute with LocalizedException.
     *
     * @return void
     */
    public function testAddWithError()
    {
        $errorMessage = __('Product with SKU %1 not found', 'product-sku');
        $this->messageManager->expects($this->once())->method('addError')->with($errorMessage);

        $this->aroundExecute(true);

        $proceed = function () {
            return true;
        };

        $this->assertInstanceOf(
            ResultInterface::class,
            $this->reorderPlugin->aroundExecute($this->subject, $proceed)
        );
    }

    /**
     * Body for aroundExecute tests.
     *
     * @param bool $disabled
     *
     * @return void
     */
    private function aroundExecute(bool $disabled)
    {
        $orderId = 1;

        $request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $request->expects($this->atLeastOnce())->method('getParam')->willReturn($orderId);
        $this->subject->expects($this->atLeastOnce())->method('getRequest')->willReturn($request);
        $this->reorder->expects($this->atLeastOnce())->method('canReorder')->willReturn(true);
        $this->orderLoader->expects($this->atLeastOnce())->method('load')->willReturn(null);
        $quoteAddress = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteAddress->expects($this->atLeastOnce())->method('importCustomerAddressData')->willReturnSelf();
        $quoteAddress->expects($this->atLeastOnce())->method('save')->willThrowException(
            new NoSuchEntityException()
        );
        $orderAddress = $this->getMockBuilder(\Magento\Sales\Model\Order\Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $shippingMethod = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quotePayment = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quotePayment->expects($this->atLeastOnce())->method('setMethod')->willReturnSelf();
        $quotePayment->expects($this->atLeastOnce())->method('save')->willReturnSelf();
        $orderPayment = $this->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $orderPayment->expects($this->atLeastOnce())->method('getMethod')->willReturn('payment_method');

        $this->order->expects($this->atLeastOnce())->method('getShippingAddress')->willReturn($orderAddress);
        $this->order->expects($this->atLeastOnce())->method('getBillingAddress')->willReturn($orderAddress);
        $this->order->expects($this->atLeastOnce())->method('getShippingMethod')->willReturn($shippingMethod);
        $this->order->expects($this->atLeastOnce())->method('getPayment')->willReturn($orderPayment);
        $this->orderRepository->expects($this->any())->method('get')->willReturn($this->order);
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quote->expects($this->atLeastOnce())->method('getShippingAddress')->willReturn($quoteAddress);
        $quote->expects($this->atLeastOnce())->method('getBillingAddress')->willReturn($quoteAddress);
        $quote->expects($this->atLeastOnce())->method('getPayment')->willReturn($quotePayment);
        $this->cart->expects($this->atLeastOnce())->method('getQuote')->willReturn($quote);
        $addressData = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->addressRepository->expects($this->atLeastOnce())->method('getById')->willReturn($addressData);
        $resultRedirect = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultRedirect->expects($this->atLeastOnce())->method('setPath')->willReturnSelf();
        $this->resultFactory->expects($this->atLeastOnce())->method('create')->willReturn($resultRedirect);

        $productId = 1;
        $product = $this->getMockBuilder(\Magento\Catalog\Api\Data\ProductInterface::class)
            ->setMethods(['isDisabled', 'getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product->method('getId')->willReturn(1);
        $product->method('isDisabled')->willReturn($disabled);
        $productCollection = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->setMethods([
                'setStore',
                'addIdFilter',
                'addStoreFilter',
                'getItems',
                'joinAttribute',
                'addAttributeToSelect',
                'addOptionsToResult',
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->productCollectionFactory->method('create')->willReturn($productCollection);
        $productCollection->method('getItems')->willReturn([$productId => $product]);
        $productCollection->method('setStore')->willReturnSelf();
        $productCollection->method('addIdFilter')->willReturnSelf();
        $productCollection->method('addStoreFilter')->willReturnSelf();
        $productCollection->method('joinAttribute')->willReturnSelf();
        $productCollection->method('addAttributeToSelect')->willReturnSelf();
        $productCollection->method('addOptionsToResult')->willReturnSelf();

        $item = $this->getMockBuilder(\Magento\Sales\Model\Order\Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSku', 'getParentItem', 'getProductId', 'getProductOptionByCode'])
            ->getMock();
        $item->expects($this->atLeastOnce())->method('getSku')->willReturn('product-sku');
        $item->expects($this->atLeastOnce())->method('getParentItem')->willReturn(null);
        $item->expects($this->atLeastOnce())->method('getProductId')->willReturn($productId);
        $itemsCollection = $this->getMockBuilder(\Magento\Sales\Model\ResourceModel\Order\Item\Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItems'])
            ->getMock();
        $itemsCollection->expects($this->atLeastOnce())->method('getItems')->willReturn([$productId => $item]);
        $this->order->expects($this->atLeastOnce())->method('getItemsCollection')->willReturn($itemsCollection);
    }

    /**
     * Test aroundExecute with result.
     *
     * @return void
     */
    public function testAroundExecuteWithResult()
    {
        $orderId = 1;
        $result = $this->getMockForAbstractClass(ResultInterface::class);
        $subject = $this->createMock(\Magento\Sales\Controller\Order\Reorder::class);
        $request = $this->getMockForAbstractClass(RequestInterface::class);
        $request->expects($this->any())->method('getParam')->willReturn($orderId);
        $subject->expects($this->any())->method('getRequest')->willReturn($request);
        $this->reorder->expects($this->atLeastOnce())->method('canReorder')->willReturn(true);
        $this->orderLoader->expects($this->any())->method('load')->willReturn($result);
        $proceed = function () {
            return true;
        };

        $this->assertInstanceOf(
            ResultInterface::class,
            $this->reorderPlugin->aroundExecute($subject, $proceed)
        );
    }
}
