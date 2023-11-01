<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Model\Plugin\Quote\Model;

use Magento\NegotiableQuote\Model\NegotiableQuoteAttributeLoader;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteRepository\LoadHandler;

/**
 * Class for applying negotiable extension attributes to a quote on load if possible.
 */
class LoadHandlerPlugin
{
    /**
     * @var NegotiableQuoteAttributeLoader
     */
    private $attributeLoader;

    /**
     * @param NegotiableQuoteAttributeLoader $attributeLoader
     */
    public function __construct(NegotiableQuoteAttributeLoader $attributeLoader)
    {
        $this->attributeLoader = $attributeLoader;
    }

    /**
     * Plugin to apply negotiable attributes to a quote before loading
     *
     * @param LoadHandler $subject
     * @param CartInterface $quote
     * @return CartInterface[]
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeLoad(LoadHandler $subject, CartInterface $quote): array
    {
        if ($quote->getExtensionAttributes() && $quote->getExtensionAttributes()->getNegotiableQuote()) {
            return [$quote];
        }
        $quote = $this->attributeLoader->loadNegotiableAttributes($quote);
        // Set negotiable quote as active to pass operation validations
        if ($quote->getExtensionAttributes()->getNegotiableQuote()->getIsRegularQuote() !== null) {
            $quote->setIsActive(true);
        }
        return [$quote];
    }
}
