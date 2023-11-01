<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PurchaseOrder\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Purchase order resource model.
 */
class PurchaseOrder extends AbstractDb
{
    /**#@+
     * Purchase order table
     */
    private const PURCHASE_ORDER_TABLE = 'purchase_order';
    /**#@-*/

    /**
     * @var \Magento\SalesSequence\Model\Manager
     */
    private $sequenceManager;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var string
     */
    private $approvedByTable = 'purchase_order_approved_by';

    /**
     * PurchaseOrder ResourceModel constructor.
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\SalesSequence\Model\Manager $sequenceManager
     * @param CartRepositoryInterface $quoteRepository
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\SalesSequence\Model\Manager $sequenceManager,
        CartRepositoryInterface $quoteRepository,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);

        $this->sequenceManager = $sequenceManager;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(self::PURCHASE_ORDER_TABLE, 'entity_id');
    }

    /**
     * Reserve increment id.
     *
     * @param int $quoteId
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function reserveIncrementId($quoteId)
    {
        $quote = $this->quoteRepository->get($quoteId, ['*']);
        return $this->sequenceManager->getSequence(
            self::PURCHASE_ORDER_TABLE,
            $quote->getStoreId()
        )->getNextValue();
    }

    /**
     * Retrieve all approved by IDs for a specific purchase order
     *
     * @param int $purchaseOrderId
     * @return array
     * @throws LocalizedException
     */
    public function getApprovedBy(int $purchaseOrderId) : array
    {
        $connection = $this->getConnection();

        $linkField = PurchaseOrderInterface::ENTITY_ID;
        $select = $connection->select()
            ->from(['poab' => $this->getTable($this->approvedByTable)], 'customer_id')
            ->join(
                ['po' => $this->getMainTable()],
                'po.' . $linkField . ' = poab.purchase_order_id',
                []
            )
            ->where('po.' . $linkField . ' = :purchase_order_id');

        return $connection->fetchCol($select, ['purchase_order_id' => (int) $purchaseOrderId]);
    }

    /**
     * Save the customer IDs who approved the PO
     *
     * @param int $purchaseOrderId
     * @param array $customerIds
     * @return $this
     */
    public function saveApprovedBy(int $purchaseOrderId, array $customerIds)
    {
        $connection = $this->getConnection();

        $table = $this->getTable($this->approvedByTable);

        if (!empty($customerIds)) {
            foreach ($customerIds as $customerId) {
                $connection->insertOnDuplicate(
                    $table,
                    ['purchase_order_id' => $purchaseOrderId, 'customer_id' => $customerId],
                    ['purchase_order_id', 'customer_id']
                );
            }

            $connection->delete(
                $table,
                ['purchase_order_id = ?' => $purchaseOrderId, 'customer_id NOT IN (?)' => $customerIds]
            );
        } else {
            $connection->delete($table, ['purchase_order_id = ?' => $purchaseOrderId]);
        }

        return $this;
    }
}
