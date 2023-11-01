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
use Magento\PurchaseOrder\Model\Notification\Action\Factory;
use Magento\PurchaseOrder\Model\Notification\Notifier\QueueMessageFactory;
use Psr\Log\LoggerInterface;

/**
 * Message queue consumer handles 'purchaseorder.trnasactional.email' message
 */
class PurchaseOrderTransEmailConsumer
{
    /**
     * @var LoggerInterface
     */
    private $purchaseOrderLogger;

    /**
     * @var QueueMessageFactory
     */
    private $queueMessageFactory;

    /**
     * @var Factory
     */
    private $actionFactory;

    /**
     * @var AppState
     */
    private $appState;

    /**
     * @var AreaList
     */
    private $areaList;

    /**
     * PurchaseOrderTransEmailConsumer constructor.
     *
     * @param LoggerInterface $purchaseOrderLogger
     * @param QueueMessageFactory $queueMessageFactory
     * @param Factory $actionFactory
     * @param AppState $appState
     * @param AreaList $areaList
     */
    public function __construct(
        LoggerInterface $purchaseOrderLogger,
        QueueMessageFactory $queueMessageFactory,
        Factory $actionFactory,
        AppState $appState,
        AreaList $areaList
    ) {
        $this->purchaseOrderLogger = $purchaseOrderLogger;
        $this->queueMessageFactory = $queueMessageFactory;
        $this->actionFactory = $actionFactory;
        $this->appState = $appState;
        $this->areaList = $areaList;
    }

    /**
     * Process transactional notification.
     *
     * @param string $serializedMessage
     */
    public function process(string $serializedMessage)
    {
        try {
            $area = $this->areaList->getArea($this->appState->getAreaCode());
            $area->load(Area::PART_TRANSLATE);

            $queueMessage = $this->queueMessageFactory->create(['serializedData' => $serializedMessage]);
            $actionInstance = $this->actionFactory->get($queueMessage->getActionClass());
            $actionInstance->notify((int)$queueMessage->getSubjectEntityId());
        } catch (LocalizedException $exception) {
            $this->purchaseOrderLogger->error('PurchaseOrderTransEmail error:' . $exception->getLogMessage());
        }
    }
}
