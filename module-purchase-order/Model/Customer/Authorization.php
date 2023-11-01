<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Customer;

use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Model\Customer\Authorization\ActionFactory;

/**
 * Purchase order customer authorization.
 */
class Authorization
{
    /**
     * @var ActionFactory
     */
    private $actionFactory;

    /**
     * @var string[]
     */
    private $actionAuthorizationPool;

    /**
     * @param ActionFactory $actionFactory
     * @param string[] $actionAuthorizationPool
     */
    public function __construct(
        ActionFactory $actionFactory,
        array $actionAuthorizationPool
    ) {
        $this->actionFactory = $actionFactory;
        $this->actionAuthorizationPool = $actionAuthorizationPool;
    }

    /**
     * Check if the current customer is allowed to perform the given action on the given purchase order.
     *
     * @param string $action
     * @param PurchaseOrderInterface $purchaseOrder
     * @return bool
     */
    public function isAllowed(string $action, PurchaseOrderInterface $purchaseOrder) : bool
    {
        $action = strtolower($action);
        $actionAuthorization = isset($this->actionAuthorizationPool[$action])
            ? $this->actionFactory->create($this->actionAuthorizationPool[$action])
            : null;
        return (null === $actionAuthorization) ? false : $actionAuthorization->isAllowed($purchaseOrder);
    }
}
