<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Model\ResourceModel;

use Magento\Quote\Api\Data\CartInterface;

/**
 * Interface GridInterface
 *
 * @api
 */
interface QuoteGridInterface
{
    /**
     * Adds new rows to the grid
     *
     * @param CartInterface $quoteData
     * @return $this
     */
    public function refresh(CartInterface $quoteData);

    /**
     * Refresh specified values for field with condition
     *
     * @param string $updateWhereField
     * @param string $updatedWhereValue
     * @param string $value
     * @param string $field
     * @return $this
     */
    public function refreshValue($updateWhereField, $updatedWhereValue, $value, $field);

    /**
     * Remove quote from quote grid
     *
     * @param CartInterface $quote
     * @return $this
     */
    public function remove(CartInterface $quote);
}
