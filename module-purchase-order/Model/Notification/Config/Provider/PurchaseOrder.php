<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Notification\Config\Provider;

use Magento\Framework\Exception\LocalizedException;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Model\Notification\Config as NotificationConfig;
use Magento\PurchaseOrder\Model\Notification\Config\ProviderInterface;
use Psr\Log\LoggerInterface;

/**
 * Config provider for purchase order entity.
 */
class PurchaseOrder implements ProviderInterface
{

    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var NotificationConfig
     */
    private $config;

    /**
     * PurchaseOrder constructor.
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param LoggerInterface $logger
     * @param NotificationConfig $config
     */
    public function __construct(
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        LoggerInterface $logger,
        NotificationConfig $config
    ) {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Check is notification enabled for entity.
     *
     * @param int $entityId
     * @return bool
     */
    public function isEnabledForEntity(int $entityId): bool
    {
        try {
            $purchaseOrder = $this->purchaseOrderRepository->getById($entityId);
            return $this->config->isEnabledForStoreView($purchaseOrder->getSnapshotQuote()->getStoreId());
        } catch (LocalizedException $exception) {
            $this->logger->critical($exception->getMessage());
        }
        return false;
    }
}
