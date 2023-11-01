<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Processor;

use Magento\Framework\ObjectManagerInterface;

/**
 * Processor factory.
 */
class Factory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Constructor
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Instantiate processor instance.
     *
     * @param string $instance
     * @return ApprovalProcessorInterface
     */
    public function create(string $instance) : ApprovalProcessorInterface
    {
        $instance = $this->objectManager->get($instance);
        if ($instance instanceof ApprovalProcessorInterface) {
            return $instance;
        } else {
            return null;
        }
    }

    /**
     * Get processor instance.
     *
     * @param string $instance
     * @return ApprovalProcessorInterface
     */
    public function get(string $instance) : ApprovalProcessorInterface
    {
        $instance = $this->objectManager->get($instance);
        if ($instance instanceof ApprovalProcessorInterface) {
            return $instance;
        } else {
            return null;
        }
    }
}
