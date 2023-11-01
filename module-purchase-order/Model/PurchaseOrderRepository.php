<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterfaceFactory;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderSearchResultsInterfaceFactory;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Model\ResourceModel\PurchaseOrder as PurchaseOrderResource;
use Magento\PurchaseOrder\Model\ResourceModel\PurchaseOrder\CollectionFactory as PurchaseOrderCollectionFactory;

/**
 * Purchase Order Repository class handles purchase order CRUD.
 */
class PurchaseOrderRepository implements PurchaseOrderRepositoryInterface
{
    /**
     * @var PurchaseOrderInterfaceFactory
     */
    private $purchaseOrderFactory;

    /**
     * @var PurchaseOrderResource
     */
    private $purchaseOrderResource;

    /**
     * @var PurchaseOrderCollectionFactory
     */
    private $purchaseOrderCollectionFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var PurchaseOrderSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @param PurchaseOrderResource $purchaseOrderResource
     * @param PurchaseOrderCollectionFactory $purchaseOrderCollectionFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param PurchaseOrderSearchResultsInterfaceFactory $searchResultsFactory
     * @param PurchaseOrderInterfaceFactory $purchaseOrderFactory
     */
    public function __construct(
        PurchaseOrderResource $purchaseOrderResource,
        PurchaseOrderCollectionFactory $purchaseOrderCollectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        PurchaseOrderSearchResultsInterfaceFactory $searchResultsFactory,
        PurchaseOrderInterfaceFactory $purchaseOrderFactory
    ) {
        $this->purchaseOrderFactory = $purchaseOrderFactory;
        $this->purchaseOrderCollectionFactory = $purchaseOrderCollectionFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->purchaseOrderResource = $purchaseOrderResource;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->purchaseOrderCollectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);
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
        $purchaseOrder = $this->purchaseOrderFactory->create();
        $this->purchaseOrderResource->load($purchaseOrder, $id);
        if ($purchaseOrder->getEntityId() != $id) {
            throw new NoSuchEntityException(
                new Phrase(
                    'No such entity with %fieldName = %fieldValue',
                    [
                        'fieldName' => 'entity_id',
                        'fieldValue' => $id
                    ]
                )
            );
        }
        return $purchaseOrder;
    }

    /**
     * @inheritdoc
     */
    public function getByQuoteId($quoteId)
    {
        $purchaseOrder = $this->purchaseOrderFactory->create();
        $this->purchaseOrderResource->load($purchaseOrder, $quoteId, 'quote_id');
        return $purchaseOrder;
    }

    /**
     * @inheritdoc
     */
    public function getByOrderId($orderId)
    {
        $purchaseOrder = $this->purchaseOrderFactory->create();
        $this->purchaseOrderResource->load($purchaseOrder, $orderId, 'order_id');
        return $purchaseOrder;
    }

    /**
     * @inheritdoc
     */
    public function save(PurchaseOrderInterface $purchaseOrder)
    {
        try {
            $this->purchaseOrderResource->save($purchaseOrder);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
    }

    /**
     * @inheritdoc
     */
    public function delete(PurchaseOrderInterface $purchaseOrder)
    {
        try {
            $this->purchaseOrderResource->delete($purchaseOrder);
        } catch (\Exception $e) {
            throw new LocalizedException(
                __(
                    'Cannot delete purchase order with id %1',
                    $purchaseOrder->getEntityId()
                ),
                $e
            );
        }
    }
}
