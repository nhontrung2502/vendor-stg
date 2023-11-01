<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrder\Plugin\Quote\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\PurchaseOrder\Model\Config;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Class to prevent Negotiable Quote from being placed via GraphQl if it is a PO since PO GraphQl isn't implemented
 */
class ValidateGraphQlSupportPlugin
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param CartRepositoryInterface $cartRepository
     * @param Config $config
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        Config $config
    ) {
        $this->cartRepository = $cartRepository;
        $this->config = $config;
    }

    /**
     * Prevent PO negotiable quote from being place using GraphQl
     *
     * @param CartManagementInterface $subject
     * @param int $cartId
     * @param PaymentInterface|null $paymentMethod
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function beforePlaceOrder(
        CartManagementInterface $subject,
        int $cartId,
        PaymentInterface $paymentMethod = null
    ): void {
        if ($this->config->isEnabledForCurrentCustomerAndWebsite()) {
            $quote = $this->cartRepository->get($cartId);
            $isNegotiable = $quote->getExtensionAttributes()->getNegotiableQuote()->getStatus() ?? false;
            if ($isNegotiable) {
                throw new GraphQlInputException(
                    __('The current customer cannot place a negotiable quote order.')
                );
            }
        }
    }
}
