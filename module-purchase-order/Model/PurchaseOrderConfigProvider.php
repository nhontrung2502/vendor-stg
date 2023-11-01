<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Framework\Api\CustomAttributesDataInterface;
use Magento\Framework\App\Action\Context;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Form\Element\Multiline;

/**
 * Class which provide purchase order data to checkout
 */
class PurchaseOrderConfigProvider implements ConfigProviderInterface
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var PaymentMethodManagementInterface
     */
    private $paymentMethodManagement;

    /**
     * @var PurchaseOrderInterface
     */
    private $purchaseOrder;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var AddressMetadataInterface
     */
    private $addressMetadata;

    /**
     * PurchaseOrderConfigProvider constructor.
     *
     * @param Context $context
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param AddressRepositoryInterface $addressRepository
     * @param PaymentMethodManagementInterface $paymentMethodManagement
     * @param UrlInterface $urlBuilder
     * @param AddressMetadataInterface $addressMetadata
     */
    public function __construct(
        Context $context,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        CartRepositoryInterface $quoteRepository,
        AddressRepositoryInterface $addressRepository,
        PaymentMethodManagementInterface $paymentMethodManagement,
        UrlInterface $urlBuilder,
        AddressMetadataInterface $addressMetadata
    ) {
        $this->context = $context;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->quoteRepository = $quoteRepository;
        $this->addressRepository = $addressRepository;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->urlBuilder = $urlBuilder;
        $this->addressMetadata = $addressMetadata;
    }

    /**
     * @inheritdoc
     */
    public function getConfig()
    {
        $purchaseOrderId = $this->context->getRequest()->getParam('purchaseOrderId');
        $isPurchaseOrder = (bool)$purchaseOrderId;
        $output = ['isPurchaseOrder' => false];

        if ($isPurchaseOrder) {
            try {
                $output = [
                    'isPurchaseOrder' => true,
                    'paymentMethods' => $this->getPaymentMethods(),
                    'purchaseOrderQuoteId' => $this->getPurchaseOrderQuote()->getId(),
                    'purchaseOrderPaymentUrl' => $this->urlBuilder->getUrl(
                        'checkout/index/index',
                        [
                            'purchaseOrderId' => $purchaseOrderId
                        ]
                    ),
                    'purchaseOrderShippingAddress' => $this->getAddressFromData(
                        $this->getPurchaseOrderQuote()->getShippingAddress()
                    )
                ];
            } catch (NoSuchEntityException $e) {
                return $output;
            }
        }

        return $output;
    }

    /**
     * Returns purchase order
     *
     * @return PurchaseOrderInterface
     * @throws NoSuchEntityException
     */
    private function getPurchaseOrder()
    {
        $purchaseOrderId = $this->context->getRequest()->getParam('purchaseOrderId');
        if ($purchaseOrderId && !$this->purchaseOrder) {
            return $this->purchaseOrderRepository->getById($purchaseOrderId);
        }
        return $this->purchaseOrder;
    }

    /**
     * Returns purchase order quote
     *
     * @return CartInterface
     * @throws NoSuchEntityException
     */
    private function getPurchaseOrderQuote()
    {
        return $this->getPurchaseOrder()->getSnapshotQuote();
    }

    /**
     * Returns array of payment methods
     *
     * @return array
     * @throws NoSuchEntityException
     */
    private function getPaymentMethods()
    {
        $paymentMethods = [];
        $quote = $this->getPurchaseOrderQuote();
        foreach ($this->paymentMethodManagement->getList($quote->getId()) as $paymentMethod) {
            $paymentMethods[] = [
                'code' => $paymentMethod->getCode(),
                'title' => $paymentMethod->getTitle()
            ];
        }
        return $paymentMethods;
    }

    /**
     * Create address data appropriate to fill checkout address form.
     *
     * @param AddressInterface $address
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getAddressFromData(AddressInterface $address)
    {
        $addressData = [];
        $attributesMetadata = $this->addressMetadata->getAllAttributesMetadata();

        foreach ($attributesMetadata as $attributeMetadata) {
            if (!$attributeMetadata->isVisible()) {
                continue;
            }
            $attributeCode = $attributeMetadata->getAttributeCode();
            $attributeData = $address->getData($attributeCode);
            if ($attributeData) {
                if ($attributeMetadata->getFrontendInput() === Multiline::NAME) {
                    $attributeData = \is_array($attributeData) ? $attributeData : explode("\n", $attributeData);
                    $attributeData = (object)$attributeData;
                }
                if ($attributeMetadata->isUserDefined()) {
                    $addressData[CustomAttributesDataInterface::CUSTOM_ATTRIBUTES][$attributeCode] = $attributeData;
                    continue;
                }
                $addressData[$attributeCode] = $attributeData;
            }
        }

        if ($address->getCustomerAddressId()) {
            $addressData[AddressInterface::CUSTOMER_ADDRESS_ID] = $address->getCustomerAddressId();
        }

        return $addressData;
    }
}
