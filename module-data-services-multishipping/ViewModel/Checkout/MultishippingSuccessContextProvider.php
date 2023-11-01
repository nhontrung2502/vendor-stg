<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DataServicesMultishipping\ViewModel\Checkout;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Multishipping\Model\Checkout\Type\Multishipping;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * ViewModel for Multishipping Checkout Success Context
 */
class MultishippingSuccessContextProvider implements ArgumentInterface
{
    /**
     * @var Multishipping
     */
    private $multishipping;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @param Multishipping $multishipping
     * @param CustomerSession $customerSession
     * @param OrderRepositoryInterface $orderRepository
     * @param Json $jsonSerializer
     */
    public function __construct(
        Multishipping $multishipping,
        CustomerSession $customerSession,
        OrderRepositoryInterface $orderRepository,
        Json $jsonSerializer
    ) {
        $this->multishipping = $multishipping;
        $this->customerSession = $customerSession;
        $this->orderRepository = $orderRepository;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Return cart id for event tracking
     *
     * @return int
     */
    public function getCartId() : int
    {
        $cartId = $this->customerSession->getDataServicesCartId();
        $this->customerSession->unsDataServicesCartId();
        return $cartId;
    }

    /**
     * Return customer email for event tracking
     *
     * @return string
     */
    public function getCustomerEmail() : string
    {
        return $this->customerSession->getCustomer()->getEmail();
    }

    /**
     * Return order ids for event tracking
     *
     * @return string
     */
    public function getOrderId() : string
    {
        return implode(',', $this->multishipping->getOrderIds());
    }

    /**
     * Return payment method data.
     * Tmp. return data only for the latest order
     *
     * @return string
     */
    public function getPayment(): string
    {
        $paymentData = [];
        foreach ($this->multishipping->getOrderIds() as $orderId) {
            $order = $this->orderRepository->get($orderId);
            $payment = $order->getPayment();
            $paymentData[$orderId] = [
                'total' => $order->getOrderCurrency()
                    ->formatPrecision(
                        $order->getBaseGrandTotal(),
                        2,
                        ['display' => \Magento\Framework\Currency::NO_SYMBOL],
                        false
                    ),
                'paymentMethodCode' => $payment->getMethod(),
                'paymentMethodName' => $payment->getMethodInstance()->getTitle(),
            ];
        }
        return $this->jsonSerializer->serialize($paymentData);
    }

    /**
     * Return shipping data
     * Tmp. return data only for the latest order
     *
     * @return string
     */
    public function getShipping(): string
    {
        $shippingData = [];
        foreach ($this->multishipping->getOrderIds() as $orderId) {
            $order = $this->orderRepository->get($orderId);
            $shippingData[$orderId] =
                [
                    'shippingMethod' => $order->getShippingMethod(),
                    'shippingAmount' => $order->getShippingAmount(),
                ];
        }
        return $this->jsonSerializer->serialize($shippingData);
    }
}
