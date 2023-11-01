<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Notification\Action\Recipient\Resolver;

use Magento\Company\Model\Company\Structure;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Model\Notification\Action\Recipient\ResolverInterface;

/**
 * Resolves parent company user in company tree structure.
 */
class PurchaseOrderCreator implements ResolverInterface
{
    /**
     * @inheritDoc
     */
    public function getRecipients(PurchaseOrderInterface $purchaseOrder): array
    {
        return [$purchaseOrder->getCreatorId()];
    }
}
