<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Notification\Action;

use Magento\Framework\ObjectManagerInterface;
use Magento\PurchaseOrder\Model\Notification\ActionNotificationInterface;

/**
 * Abstract action factory.
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
     * Get action instance
     *
     * @param string $actionInstanceClass
     * @return ActionNotificationInterface
     */
    public function get(string $actionInstanceClass) : ActionNotificationInterface
    {
        return $this->objectManager->get($actionInstanceClass);
    }
}
