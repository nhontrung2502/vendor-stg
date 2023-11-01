<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\QueueConsumer;

use Magento\Framework\App\Area;
use Magento\Framework\App\AreaList;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Exception\LocalizedException;
use Magento\PurchaseOrder\Api\PurchaseOrderManagementInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Message queue consumer handles 'purchaseorder.toorder' message
 */
class PurchaseOrderToOrderConsumer
{
    /**
     * @var LoggerInterface
     */
    private $purchaseOrderLogger;

    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * @var PurchaseOrderManagementInterface
     */
    private $purchaseOrderManagement;

    /**
     * @var AppState
     */
    private $appState;

    /**
     * @var AreaList
     */
    private $areaList;

    /**
     * @param LoggerInterface $purchaseOrderLogger
     * @param PurchaseOrderManagementInterface $purchaseOrderManagement
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param AppState $appState
     * @param AreaList $areaList
     */
    public function __construct(
        LoggerInterface $purchaseOrderLogger,
        PurchaseOrderManagementInterface $purchaseOrderManagement,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        AppState $appState,
        AreaList $areaList
    ) {
        $this->purchaseOrderLogger = $purchaseOrderLogger;
        $this->purchaseOrderManagement = $purchaseOrderManagement;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->appState = $appState;
        $this->areaList = $areaList;
    }

    /**
     * Process purchase order
     *
     * @param string $purchaseOrderId
     */
    public function process(string $purchaseOrderId)
    {
        try {
            $area = $this->areaList->getArea($this->appState->getAreaCode());
            $area->load(Area::PART_TRANSLATE);

            $purchaseOrder = $this->purchaseOrderRepository->getById($purchaseOrderId);
            $this->purchaseOrderManagement->createSalesOrder($purchaseOrder);
        } catch (LocalizedException $exception) {
            $this->purchaseOrderLogger->error('PurchaseOrderToOrderConsumer error:' . $exception->getLogMessage());
        }
    }
}
