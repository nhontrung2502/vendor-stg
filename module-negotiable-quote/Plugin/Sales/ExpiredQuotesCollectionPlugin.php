<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Plugin\Sales;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\NegotiableQuote\Model\ResourceModel\NegotiableQuote;
use Magento\Sales\Model\ResourceModel\Collection\ExpiredQuotesCollection;

/**
 * Plugin that updates Expired Quotes Collection with constraints that filter out Negotiable Quotes.
 */
class ExpiredQuotesCollectionPlugin
{
    /**
     * Updates Expired Quotes Collection with constraints that filter out Negotiable Quotes
     *
     * @param ExpiredQuotesCollection $subject
     * @param AbstractCollection $result
     * @return AbstractCollection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetExpiredQuotes(
        ExpiredQuotesCollection $subject,
        AbstractCollection $result
    ): AbstractCollection {
        $negotiableQuoteTable = $result->getTable(NegotiableQuote::NEGOTIABLE_QUOTE_TABLE);
        $result->getSelect()
        ->joinLeft(
            $negotiableQuoteTable,
            "main_table.entity_id = " . $negotiableQuoteTable . ".quote_id"
        )->where($negotiableQuoteTable . ".quote_id is NULL");

        return $result;
    }
}
