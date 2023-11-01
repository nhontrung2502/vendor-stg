<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Block\Quote;

use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;

/**
 * Block for preparing quote view data.
 *
 * @api
 * @since 100.0.0
 */
class View extends AbstractQuote
{
    /**
     * Set page title.
     * @since 100.2.0
     */
    protected function _prepareLayout()
    {
        $negotiableQuote = $this->getNegotiableQuote();
        if ($negotiableQuote && $negotiableQuote->getQuoteName()) {
            $quoteName = $negotiableQuote->getQuoteName();
        }
        $this->pageConfig->getTitle()->set(__('Quote %1', $quoteName));
    }

    /**
     * Get url for quote recalculate action.
     *
     * @return string
     */
    public function getRecalculateUrl()
    {
        return $this->getUrl('negotiable_quote/quote/recalculate');
    }

    /**
     * Get quote id.
     *
     * @return int
     */
    public function getQuoteId()
    {
        return $this->getQuote()->getId();
    }

    /**
     * Check status is not ordered or closed.
     *
     * @return bool
     */
    public function isCanRecalculate()
    {
        return $this->getNegotiableQuote()->getStatus() != NegotiableQuoteInterface::STATUS_ORDERED
            && $this->getNegotiableQuote()->getStatus() != NegotiableQuoteInterface::STATUS_CLOSED;
    }
}
