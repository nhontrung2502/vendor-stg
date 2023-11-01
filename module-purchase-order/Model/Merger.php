<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model;

use Magento\Framework\MessageQueue\MergerInterface;

/**
 * Merges messages from the operations queue.
 */
class Merger implements MergerInterface
{
    /**
     * @inheritDoc
     */
    public function merge(array $messages)
    {
        return $messages;
    }
}
