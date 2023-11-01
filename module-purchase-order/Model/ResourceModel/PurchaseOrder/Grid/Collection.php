<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\ResourceModel\PurchaseOrder\Grid;

use Magento\Company\Model\Company\Structure as CompanyStructure;
use Magento\Company\Model\CompanyContext;
use Magento\Company\Model\ResourceModel\Customer as CustomerResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Magento\PurchaseOrder\Model\ResourceModel\PurchaseOrder\Collection as PurchaseOrderCollection;
use Psr\Log\LoggerInterface as Logger;
use Magento\PurchaseOrder\Model\Config\Source\Status as PurchaseOrderStatus;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;

/**
 * Collection class for the purchase order grid.
 */
class Collection extends SearchResult
{
    /**
     * @var CompanyContext
     */
    private $companyContext;

    /**
     * @var CustomerResource
     */
    private $customerResource;

    /**
     * @var CompanyStructure
     */
    private $companyStructure;

    /**
     * @var bool
     */
    private $filterCurrentCustomer;

    /**
     * @var PurchaseOrderStatus
     */
    private $purchaseOrderStatus;

    /**
     * @param CompanyContext $companyContext
     * @param CustomerResource $customerResource
     * @param CompanyStructure $companyStructure
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param PurchaseOrderStatus $purchaseOrderStatus
     * @param string $mainTable
     * @param string $resourceModel
     * @param bool $filterCurrentCustomer
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        CompanyContext $companyContext,
        CustomerResource $customerResource,
        CompanyStructure $companyStructure,
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        PurchaseOrderStatus $purchaseOrderStatus,
        $mainTable = 'purchase_order',
        $resourceModel = PurchaseOrderCollection::class,
        $filterCurrentCustomer = false
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
        $this->companyContext = $companyContext;
        $this->customerResource = $customerResource;
        $this->companyStructure = $companyStructure;
        $this->filterCurrentCustomer = $filterCurrentCustomer;
        $this->purchaseOrderStatus = $purchaseOrderStatus;
    }

    /**
     * @inheritDoc
     */
    public function _beforeLoad()
    {
        $this->applyDefaultJoinsToSelect();
        $this->applyDefaultFiltersToSelect();

        return parent::_beforeLoad();
    }

    /**
     * Apply the default JOIN clauses specific to the purchase order grid.
     */
    private function applyDefaultJoinsToSelect()
    {
        // Include data for the purchase order creator
        $this->getSelect()->joinLeft(
            ['customer_creator' => $this->getTable('customer_entity')],
            'customer_creator.entity_id = main_table.creator_id',
            ['creator_name' => "CONCAT(customer_creator.firstname, ' ', customer_creator.lastname)"]
        );
    }

    /**
     * Apply the default filters specific to the purchase order grid.
     *
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function applyDefaultFiltersToSelect()
    {
        $customerId = $this->companyContext->getCustomerId();
        $customerExtAttr = $this->customerResource->getCustomerExtensionAttributes($customerId);
        $companyId = isset($customerExtAttr['company_id']) ? $customerExtAttr['company_id'] : null;
        $this->addFieldToFilter('main_table.company_id', $companyId);

        if ($companyId === null) {
            return;
        }

        if ($this->filterCurrentCustomer) {
            $this->applyCustomerFilter($customerId);
        } else {
            $this->applyCompanyFilter($companyId, $customerId);
        }
    }

    /**
     * Filter collection by creator id.
     *
     * @param int $customerId
     */
    private function applyCustomerFilter($customerId)
    {
        if ($this->companyContext->isResourceAllowed('Magento_PurchaseOrder::view_purchase_orders')) {
            $this->addFieldToFilter('main_table.creator_id', ["eq" => $customerId]);
        }
    }

    /**
     * Filter collection by company.
     *
     * @param int $companyId
     * @param int $customerId
     * @throws LocalizedException
     */
    private function applyCompanyFilter($companyId, $customerId)
    {
        $allowedCustomerIds = [];
        if ($this->companyContext->isResourceAllowed('Magento_PurchaseOrder::view_purchase_orders')) {
            $allowedCustomerIds[] = $customerId;

            if ($this->companyContext->isResourceAllowed(
                'Magento_PurchaseOrder::view_purchase_orders_for_company'
            )) {
                $companyCustomers = $this->customerResource->getCustomerIdsByCompanyId($companyId);
                $allowedCustomerIds = array_merge($allowedCustomerIds, $companyCustomers);
            } elseif ($this->companyContext->isResourceAllowed(
                'Magento_PurchaseOrder::view_purchase_orders_for_subordinates'
            )) {
                $subordinates = $this->companyStructure->getAllowedChildrenIds($customerId);
                $allowedCustomerIds = array_merge($allowedCustomerIds, $subordinates);
            }

            $allowedCustomerIds = array_unique($allowedCustomerIds);
        }
        $this->addFieldToFilter('main_table.creator_id', ["in" => $allowedCustomerIds]);
    }

    /**
     * Add custom sort order for status field based on case statement
     *
     * @param string $field
     * @param string $direction
     * @return $this
     */
    public function setOrder($field, $direction = self::SORT_ORDER_DESC)
    {
        if ($field === PurchaseOrderInterface::PO_STATUS) {
            $arrayStatuses = $this->purchaseOrderStatus->getStatusLabels();
            $caseConditions = [];
            foreach ($arrayStatuses as $key => $status) {
                $case = $this->getConnection()->quoteInto('?', $key);
                $result = $this->getConnection()->quoteInto('?', $status->render());
                $caseConditions[$case] = $result;
            }
            $caseSql = $this->getConnection()->getCaseSql('status', $caseConditions);
            $this->getSelect()->order(new \Zend_Db_Expr($caseSql . ' ' . $direction));
            return $this;
        } else {
            return parent::setOrder($field, $direction);
        }
    }
}
