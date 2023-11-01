<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Processor;

use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Model\Processor\Exception\ApprovalProcessorException;

/**
 * Composite processor used to combine processors.
 */
class ApprovalsComposite implements ApprovalProcessorInterface
{
    /**
     * @var ApprovalProcessorInterface[]
     */
    private $processors = [];

    /**
     * @var string[]
     */
    private $processorClasses = [];

    /**
     * @var Factory
     */
    private $factory;

    /**
     * Composite constructor.
     *
     * @param Factory $factory
     * @param array $processors
     */
    public function __construct(
        Factory $factory,
        array $processors = []
    ) {
        $this->factory = $factory;
        usort(
            $processors,
            function ($a, $b) {
                return $a['priority'] - $b['priority'];
            }
        );
        foreach ($processors as $processor) {
            $this->processorClasses []= $processor['processorClass'];
        }
    }

    /**
     * @inheritDoc
     */
    public function processApproval(PurchaseOrderInterface $purchaseOrder, int $customerId)
    {
        try {
            foreach ($this->processorClasses as $processorClass) {
                $this->getProcessor($processorClass)->processApproval($purchaseOrder, $customerId);
            }
        } catch (ApprovalProcessorException $e) {
            // todo: purchase order approvals processor exception process
            throw $e;
        }
    }

    /**
     * Get processor instance.
     *
     * @param string $class
     * @return ApprovalProcessorInterface
     */
    private function getProcessor($class)
    {
        if (empty($this->processors[$class]) || !$this->processors[$class] instanceof ApprovalProcessorInterface) {
            $this->processors[$class] = $this->factory->create($class);
        }
        return $this->processors[$class];
    }
}
