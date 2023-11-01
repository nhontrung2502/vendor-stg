<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Plugin\Checkout\Model;

use Magento\Checkout\Block\Onepage;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Company\Model\CompanyManagement;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\PurchaseOrder\Model\Config as PurchaseOrderConfig;
use Magento\PurchaseOrder\Model\Payment\DeferredPaymentStrategyInterface;
use Magento\PurchaseOrder\Model\PurchaseOrderRepository;
use Magento\Quote\Api\PaymentMethodManagementInterface;

/**
 * Plugin to provide additional configuration data for checkout
 */
class ConfigProvider
{
    /**
     * @var PurchaseOrderConfig
     */
    private $purchaseOrderConfig;

    /**
     * @var CompanyManagement
     */
    private $companyManagement;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var PurchaseOrderRepository
     */
    private $purchaseOrderRepository;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var PaymentMethodManagementInterface
     */
    private $paymentMethodManagement;

    /**
     * @var DeferredPaymentStrategyInterface|null
     */
    private $deferredPaymentStrategy;

    /**
     * @param CheckoutSession $checkoutSession
     * @param PurchaseOrderConfig $purchaseOrderConfig
     * @param CompanyManagement $companyManagement
     * @param UrlInterface $urlBuilder
     * @param PurchaseOrderRepository $purchaseOrderRepository
     * @param PaymentMethodManagementInterface $paymentMethodManagement
     * @param DeferredPaymentStrategyInterface|null $deferredPaymentStrategy
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        PurchaseOrderConfig $purchaseOrderConfig,
        CompanyManagement $companyManagement,
        UrlInterface $urlBuilder,
        PurchaseOrderRepository $purchaseOrderRepository,
        PaymentMethodManagementInterface $paymentMethodManagement,
        DeferredPaymentStrategyInterface $deferredPaymentStrategy = null
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->purchaseOrderConfig = $purchaseOrderConfig;
        $this->companyManagement = $companyManagement;
        $this->urlBuilder = $urlBuilder;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->deferredPaymentStrategy = $deferredPaymentStrategy ?:
            ObjectManager::getInstance()->get(DeferredPaymentStrategyInterface::class);
    }

    /**
     * Add purchase order configuration to checkout config array
     *
     * @param Onepage $subject
     * @param array $result
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetCheckoutConfig(Onepage $subject, array $result)
    {
        if (!isset($result['isCustomerLoggedIn']) || !$result['isCustomerLoggedIn']) {
            return $result;
        }

        $result['isPurchaseOrderEnabled'] = $this->purchaseOrderConfig->isEnabledForCurrentCustomerAndWebsite();

        if (!$result['isPurchaseOrderEnabled']) {
            return $result;
        }

        $customerId = $result['customerData']['id'];

        $company = $this->companyManagement->getByCustomerId($customerId);

        $result['customerData']['isCompanyUser'] = (bool) $company;

        if (!$company) {
            return $result;
        }

        $result['poSuccessPageUrl'] = $this->urlBuilder->getUrl('purchaseorder/purchaseorder/success');

        $result['paymentMethods'] = $this->getPaymentMethods();

        return $result;
    }

    /**
     * Returns an array of available payment methods for checkout.
     *
     * @return MethodInterface[] $paymentMethods
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getPaymentMethods()
    {
        $paymentMethods = [];
        $quote = $this->checkoutSession->getQuote();

        foreach ($this->paymentMethodManagement->getList($quote->getId()) as $paymentMethod) {
            $paymentMethods[] = [
                'code' => $paymentMethod->getCode(),
                'title' => $paymentMethod->getTitle(),
                'is_deferred' => $this->deferredPaymentStrategy->isDeferrablePaymentMethod($paymentMethod->getCode())
            ];
        }

        return $paymentMethods;
    }
}
