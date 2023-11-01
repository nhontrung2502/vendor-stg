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
class CreatorStructureParent implements ResolverInterface
{
    /**
     * @var Structure
     */
    private $structure;

    /**
     * CreatorStructureParent constructor.
     *
     * @param Structure $structure
     */
    public function __construct(
        Structure $structure
    ) {
        $this->structure = $structure;
    }

    /**
     * @inheritDoc
     */
    public function getRecipients(PurchaseOrderInterface $purchaseOrder): array
    {
        $structure = $this->structure->getStructureByCustomerId($purchaseOrder->getCreatorId());
        $parentNode = $this->structure->getTreeById($structure->getParentId());
        $parentCustomerId = $parentNode->getEntityId();
        if (!empty($parentCustomerId)) {
            return [$parentCustomerId];
        } else {
            return [];
        }
    }
}
