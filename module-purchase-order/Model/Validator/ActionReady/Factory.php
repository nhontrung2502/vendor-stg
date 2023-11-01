<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Validator\ActionReady;

use Magento\Framework\ObjectManagerInterface;

/**
 * Validator factory.
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
     * Get validator instance.
     *
     * @param string $instance
     * @return ValidatorInterface
     */
    public function get(string $instance) : ValidatorInterface
    {
        $instance = $this->objectManager->get($instance);
        if ($instance instanceof ValidatorInterface) {
            return $instance;
        } else {
            return null;
        }
    }
}
