<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Notification\Config\Provider;

use Magento\Framework\Exception\LocalizedException;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Model\CommentRepositoryInterface;
use Magento\PurchaseOrder\Model\Notification\Config as NotificationConfig;
use Magento\PurchaseOrder\Model\Notification\Config\ProviderInterface;
use Psr\Log\LoggerInterface;

/**
 * Config provider for purchase order comment entity.
 */
class PurchaseOrderComment implements ProviderInterface
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
     * @var CommentRepositoryInterface
     */
    private $commentRepository;

    /**
     * PurchaseOrderComment constructor.
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param CommentRepositoryInterface $commentRepository
     * @param LoggerInterface $logger
     * @param NotificationConfig $config
     */
    public function __construct(
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        CommentRepositoryInterface $commentRepository,
        LoggerInterface $logger,
        NotificationConfig $config
    ) {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->logger = $logger;
        $this->config = $config;
        $this->commentRepository = $commentRepository;
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
            $comment = $this->commentRepository->get($entityId);
            $purchaseOrder = $this->purchaseOrderRepository->getById($comment->getPurchaseOrderId());
            return $this->config->isEnabledForStoreView($purchaseOrder->getSnapshotQuote()->getStoreId());
        } catch (LocalizedException $exception) {
            $this->logger->critical($exception->getMessage());
        }
        return false;
    }
}
