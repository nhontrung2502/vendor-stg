<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Ui\Component;

use Magento\Company\Model\CompanyContext;
use Magento\Company\Model\CompanyManagement;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\RequestInterface;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;

/**
 * DataProvider for purchase orders selection on grid
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    /**
     * Tab name on Purchase Orders listing page
     */
    const TAB_NAME = 'company';

    /**
     * @var array|CompanyManagement
     */
    private $companyManagement;

    /**
     * @var array|CompanyContext
     */
    private $companyContext;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param CompanyManagement $companyManagement
     * @param CompanyContext $companyContext
     * @param array $meta
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        CompanyManagement $companyManagement,
        CompanyContext $companyContext,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
        $this->companyManagement = $companyManagement;
        $this->companyContext = $companyContext;
    }

    /**
     * @inheritdoc
     */
    protected function searchResultToOutput(SearchResultInterface $searchResult)
    {
        $items = parent::searchResultToOutput($searchResult);
        $items['totalFilteredRecords'] = $this->getFilteredTotalCount();
        $items['tabName'] = self::TAB_NAME;

        return $items;
    }

    /**
     * Get total count filtered by status.
     *
     * @return int
     */
    private function getFilteredTotalCount()
    {
        $customerId = $this->companyContext->getCustomerId();
        $companyId =$this->companyManagement->getByCustomerId($customerId)->getId();
        $allowedStatuses = [PurchaseOrderInterface::STATUS_APPROVAL_REQUIRED, PurchaseOrderInterface::STATUS_PENDING];
        $searchCriteriaBuilder = clone $this->searchCriteriaBuilder;
        $searchCriteriaBuilder->addFilter(
            $this->filterBuilder->setField('main_table.' . PurchaseOrderInterface::PO_STATUS)
                                ->setValue($allowedStatuses)
                                ->setConditionType('in')
                                ->create()
        );
        $searchCriteriaBuilder->addFilter(
            $this->filterBuilder->setField('main_table.' . PurchaseOrderInterface::COMPANY_ID)
                                ->setValue($companyId)
                                ->setConditionType('eq')
                                ->create()
        );
        $searchCriteria = $searchCriteriaBuilder->create();
        $searchCriteria->setRequestName($this->name);
        $searchResult = $this->reporting->search($searchCriteria);

        return $searchResult->getTotalCount();
    }
}
