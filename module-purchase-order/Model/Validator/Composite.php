<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Validator;

use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderManagementInterface;
use Magento\PurchaseOrder\Model\Validator\Exception\PurchaseOrderValidationException;

/**
 * Composite validator used to combine validators.
 */
class Composite implements ValidatorInterface
{
    /**
     * @var ValidatorInterface[]
     */
    private $validators = [];

    /**
     * @var string[]
     */
    private $validatorClasses = [];

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var PurchaseOrderManagementInterface
     */
    private $purchaseOrderManagement;

    /**
     * Composite constructor.
     *
     * @param Factory $factory
     * @param PurchaseOrderManagementInterface $purchaseOrderManagement
     * @param array $validators
     */
    public function __construct(
        Factory $factory,
        PurchaseOrderManagementInterface $purchaseOrderManagement,
        array $validators = []
    ) {
        $this->factory = $factory;
        $this->purchaseOrderManagement = $purchaseOrderManagement;
        usort(
            $validators,
            function ($a, $b) {
                return $a['priority'] - $b['priority'];
            }
        );
        foreach ($validators as $validator) {
            $this->validatorClasses []= $validator['validatorClass'];
        }
    }

    /**
     * @inheritDoc
     */
    public function validate(PurchaseOrderInterface $purchaseOrder): void
    {
        try {
            if (!empty($this->validatorClasses)) {
                foreach ($this->validatorClasses as $validatorClass) {
                    $this->getValidator($validatorClass)->validate($purchaseOrder);
                }
            } else {
                $this->purchaseOrderManagement->setApprovalRequired($purchaseOrder);
            }
        } catch (PurchaseOrderValidationException $e) {
            // todo: purchase order validation exception process
            throw $e;
        }
    }

    /**
     * Get validator instance.
     *
     * @param string $class
     * @return ValidatorInterface
     */
    private function getValidator($class)
    {
        if (empty($this->validators[$class]) || !$this->validators[$class] instanceof ValidatorInterface) {
            $this->validators[$class] = $this->factory->create($class);
        }
        return $this->validators[$class];
    }
}
