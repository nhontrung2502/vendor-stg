<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Customer\Authorization;

/**
 * Factory class for @see \Magento\PurchaseOrder\Model\Customer\Authorization\ActionInterface
 */
class ActionFactory
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Factory constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * Create action authorization instance
     *
     * @param string $actionAuthorizationInstanceName
     * @return ActionInterface|null
     */
    public function create(string $actionAuthorizationInstanceName)
    {
        $instance = $this->objectManager->create($actionAuthorizationInstanceName);
        if ($instance instanceof ActionInterface) {
            return $instance;
        } else {
            return null;
        }
    }
}
