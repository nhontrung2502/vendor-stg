<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Notification\Email\ContentSource;

use Magento\Framework\ObjectManagerInterface;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Model\Notification\ContentSourceInterface;

/**
 * Content source factory.
 */
class Factory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Factory constructor.
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * Create content source instance.
     *
     * @param string $contentSourceClass
     * @param PurchaseOrderInterface $purchaseOrder
     * @param int $recipientId
     * @param array $data
     * @return ContentSourceInterface
     */
    public function create(
        string $contentSourceClass,
        PurchaseOrderInterface $purchaseOrder,
        int $recipientId,
        array $data = []
    ) : ContentSourceInterface {
        return $this->objectManager->create(
            $contentSourceClass,
            [
                'purchaseOrder' => $purchaseOrder,
                'recipientId' => $recipientId,
                'data' => $data
            ]
        );
    }
}
