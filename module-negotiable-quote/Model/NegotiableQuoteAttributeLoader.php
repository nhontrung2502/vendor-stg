<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Class for applying negotiable quote attributes to the base quote before it is loaded
 */
class NegotiableQuoteAttributeLoader
{
    /**
     * @var CartExtensionFactory
     */
    private $cartExtensionFactory;

    /**
     * @var NegotiableQuoteRepositoryInterface
     */
    private $negotiableQuoteRepository;

    /**
     * @var RestrictionInterface
     */
    private $restriction;

    /**
     * @param CartExtensionFactory $cartExtensionFactory
     * @param RestrictionInterface $restriction
     * @param NegotiableQuoteRepositoryInterface $negotiableQuoteRepository
     */
    public function __construct(
        CartExtensionFactory $cartExtensionFactory,
        RestrictionInterface $restriction,
        NegotiableQuoteRepositoryInterface $negotiableQuoteRepository
    ) {
        $this->cartExtensionFactory = $cartExtensionFactory;
        $this->restriction = $restriction;
        $this->negotiableQuoteRepository = $negotiableQuoteRepository;
    }

    /**
     * Apply negotiable attributes to the corresponding base quote
     *
     * @param CartInterface $quote
     * @return CartInterface
     * @throws NoSuchEntityException
     */
    public function loadNegotiableAttributes(CartInterface $quote): CartInterface
    {
        try {
            $negotiableQuote = $this->negotiableQuoteRepository->getById($quote->getId());
            if ($negotiableQuote && $negotiableQuote->getIsRegularQuote()) {
                $this->restriction->setQuote($quote);
            }
        } catch (\Exception $e) {
            throw new NoSuchEntityException(__('Negotiated quote not found.'));
        }
        if ($negotiableQuote->getIsRegularQuote()
            && $quote->getCustomerGroupId() != $quote->getCustomer()->getGroupId()
        ) {
            $quote->unsetData('customer_group_id');
        }
        $quoteExtension = $quote->getExtensionAttributes() ?: $this->cartExtensionFactory->create();
        $quote->setExtensionAttributes($quoteExtension->setNegotiableQuote($negotiableQuote));
        return $quote;
    }
}
