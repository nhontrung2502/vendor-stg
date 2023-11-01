<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PurchaseOrder\Model;

use Magento\PurchaseOrder\Api\Data\PurchaseOrderLogInterface;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderLogInterfaceFactory;
use Magento\PurchaseOrder\Model\ResourceModel\PurchaseOrderLog;
use Magento\PurchaseOrder\Model\ResourceModel\PurchaseOrderLog\CollectionFactory as PurchaseOrderLogCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface as SearchCriteriaCollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsFactory;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Purchase Order Repository class handles purchase order log CRUD
 */
class PurchaseOrderLogRepository implements PurchaseOrderLogRepositoryInterface
{
    /**
     * @var PurchaseOrderLogInterfaceFactory
     */
    private $purchaseOrderLogFactory;

    /**
     * @var PurchaseOrderLog
     */
    private $purchaseOrderLogResource;

    /**
     * @var PurchaseOrderLogCollectionFactory
     */
    private $purchaseOrderLogCollectionFactory;

    /**
     * @var SearchCriteriaCollectionProcessorInterface
     */
    private $searchCriteriaCollectionProcessor;

    /**
     * @var SearchResultsFactory
     */
    private $searchResultsFactory;

    /**
     * PurchaseOrderLogRepository constructor.
     *
     * @param PurchaseOrderLog $requestLogResource
     * @param PurchaseOrderLogCollectionFactory $purchaseOrderLogCollectionFactory
     * @param SearchCriteriaCollectionProcessorInterface $searchCriteriaCollectionProcessor
     * @param SearchResultsFactory $searchResultsFactory
     * @param PurchaseOrderLogInterfaceFactory $PurchaseOrderLogInterfaceFactory
     */
    public function __construct(
        PurchaseOrderLog $requestLogResource,
        PurchaseOrderLogCollectionFactory $purchaseOrderLogCollectionFactory,
        SearchCriteriaCollectionProcessorInterface $searchCriteriaCollectionProcessor,
        SearchResultsFactory $searchResultsFactory,
        PurchaseOrderLogInterfaceFactory $PurchaseOrderLogInterfaceFactory
    ) {
        $this->purchaseOrderLogFactory = $PurchaseOrderLogInterfaceFactory;
        $this->purchaseOrderLogCollectionFactory = $purchaseOrderLogCollectionFactory;
        $this->searchCriteriaCollectionProcessor = $searchCriteriaCollectionProcessor;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->purchaseOrderLogResource = $requestLogResource;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->purchaseOrderLogCollectionFactory->create();
        $this->searchCriteriaCollectionProcessor->process($searchCriteria, $collection);
        $collection->load();
        $searchResult = $this->searchResultsFactory->create();
        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collection->getSize());
        return $searchResult;
    }

    /**
     * @inheritdoc
     */
    public function getById($id)
    {
        $log = $this->purchaseOrderLogFactory->create()->load($id);
        return $log;
    }

    /**
     * @inheritdoc
     */
    public function save(PurchaseOrderLogInterface $log)
    {
        try {
            $this->purchaseOrderLogResource->save($log);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return true;
    }
}
