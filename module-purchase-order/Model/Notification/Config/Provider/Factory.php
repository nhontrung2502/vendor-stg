<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Notification\Config\Provider;

use Magento\Framework\ObjectManagerInterface;
use Magento\PurchaseOrder\Model\Notification\Config\ProviderInterface;

/**
 * Config provider factory.
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
     * Create config provider instance.
     *
     * @param string $class
     * @return ProviderInterface
     */
    public function create(string $class) : ProviderInterface
    {
        return $this->objectManager->get($class);
    }
}
