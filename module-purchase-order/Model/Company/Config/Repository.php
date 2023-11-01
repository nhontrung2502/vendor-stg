<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PurchaseOrder\Model\Company\Config;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\PurchaseOrder\Model\Company\ConfigInterface;
use Magento\PurchaseOrder\Model\Company\ConfigInterfaceFactory;
use Magento\PurchaseOrder\Model\ResourceModel\Company\Config as ConfigResourceModel;

/**
 * Company purchase order config repository model
 */
class Repository implements RepositoryInterface
{
    /**
     * @var ConfigResourceModel
     */
    private $configResourceModel;

    /**
     * @var ConfigInterfaceFactory
     */
    private $configFactory;

    /**
     * @param ConfigResourceModel $configResourceModel
     * @param ConfigInterfaceFactory $configFactory
     */
    public function __construct(
        ConfigResourceModel $configResourceModel,
        ConfigInterfaceFactory $configFactory
    ) {
        $this->configResourceModel = $configResourceModel;
        $this->configFactory = $configFactory;
    }

    /**
     * @inheritDoc
     */
    public function get($companyId)
    {
        $config = $this->configFactory->create();

        $this->configResourceModel->load($config, $companyId);

        return $config;
    }

    /**
     * @inheritdoc
     */
    public function save(ConfigInterface $companyConfig)
    {
        try {
            $this->configResourceModel->save($companyConfig);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'There was an error saving purchase order configuration for this company.'
            ));
        }

        return $companyConfig;
    }
}
