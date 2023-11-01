<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\NonTransactionableInterface;
use Magento\SalesSequence\Model\Builder as SequenceBuilder;
use Magento\SalesSequence\Model\Config as SequenceConfig;
use Magento\Store\Api\StoreRepositoryInterface;

/**
 * Initializes purchase order sales sequence.
 */
class InitPurchaseOrderSalesSequence implements DataPatchInterface, NonTransactionableInterface
{
    /**
     * @var SequenceBuilder
     */
    private $sequenceBuilder;

    /**
     * @var SequenceConfig
     */
    private $sequenceConfig;

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * InitPurchaseOrderSalesSequence constructor.
     * @param SequenceBuilder $sequenceBuilder
     * @param SequenceConfig $sequenceConfig
     * @param StoreRepositoryInterface $storeRepository
     */
    public function __construct(
        SequenceBuilder $sequenceBuilder,
        SequenceConfig $sequenceConfig,
        StoreRepositoryInterface $storeRepository
    ) {
        $this->sequenceBuilder = $sequenceBuilder;
        $this->sequenceConfig = $sequenceConfig;
        $this->storeRepository = $storeRepository;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $stores = $this->storeRepository->getList();

        foreach ($stores as $store) {
            $storeId = $store->getId();

            $this->sequenceBuilder->setPrefix($storeId ?: $this->sequenceConfig->get('prefix'))
                ->setSuffix($this->sequenceConfig->get('suffix'))
                ->setStartValue($this->sequenceConfig->get('startValue'))
                ->setStoreId($storeId)
                ->setStep($this->sequenceConfig->get('step'))
                ->setWarningValue($this->sequenceConfig->get('warningValue'))
                ->setMaxValue($this->sequenceConfig->get('maxValue'))
                ->setEntityType('purchase_order')
                ->create();
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
