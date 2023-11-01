<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Validator\ActionReady;

use Magento\PurchaseOrder\Model\Validator\ActionReady\ValidatorInterface as ActionReadyValidatorInterface;

/**
 * Action ready validator locator locates action ready validator instance
 */
class ValidatorLocator
{
    /**
     * @var Factory
     */
    private $validatorFactory;

    /**
     * @var string[]
     */
    private $actionReadyValidatorPool;

    /**
     * @param Factory $validatorFactory
     * @param string[] $actionReadyValidatorPool
     */
    public function __construct(
        Factory $validatorFactory,
        array $actionReadyValidatorPool
    ) {
        $this->validatorFactory = $validatorFactory;
        $this->actionReadyValidatorPool = $actionReadyValidatorPool;
    }

    /**
     * Get action ready validator.
     *
     * @param string $action
     * @return ValidatorInterface
     */
    public function getValidator(string $action)
    {
        $validator = $this->validatorFactory->get($this->actionReadyValidatorPool[strtolower($action)]);
        if ($validator instanceof ActionReadyValidatorInterface) {
            return $validator;
        } else {
            return $this->validatorFactory->get(DefaultValidator::class);
        }
    }
}
