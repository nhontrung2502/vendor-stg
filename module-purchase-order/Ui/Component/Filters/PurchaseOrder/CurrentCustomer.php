<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PurchaseOrder\Ui\Component\Filters\PurchaseOrder;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Api\AuthorizationInterface;
use Magento\Company\Model\Company\Structure as CompanyStructure;
use Magento\Company\Model\ResourceModel\Customer as CustomerResource;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Filters\FilterModifier;
use Magento\Ui\Component\Filters\Type\AbstractFilter;

/**
 * Filter purchase orders by current customer.
 */
class CurrentCustomer extends AbstractFilter
{
    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var CompanyStructure
     */
    private $companyStructure;

    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @var CustomerResource
     */
    private $customerResource;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param FilterBuilder $filterBuilder
     * @param FilterModifier $filterModifier
     * @param UserContextInterface $userContext
     * @param CompanyStructure $companyStructure
     * @param AuthorizationInterface $authorization
     * @param CustomerResource $customerResource
     * @param array $components
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        FilterBuilder $filterBuilder,
        FilterModifier $filterModifier,
        UserContextInterface $userContext,
        CompanyStructure $companyStructure,
        AuthorizationInterface $authorization,
        CustomerResource $customerResource,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $filterBuilder, $filterModifier, $components, $data);

        $this->userContext = $userContext;
        $this->companyStructure = $companyStructure;
        $this->authorization = $authorization;
        $this->customerResource = $customerResource;
    }

    /**
     * @inheritdoc
     */
    public function prepare()
    {
        $config = (array) $this->getData('config');
        $config['visible'] = $this->isVisible();

        $this->setData('config', $config);
        $this->applyFilter();

        parent::prepare();
    }

    /**
     * Apply this filter on the data provider.
     */
    private function applyFilter()
    {
        $showOnlyCurrentCustomerFilter = $this->filterData[$this->getName()] ?? null;

        if ($showOnlyCurrentCustomerFilter) {
            $filter = $this->filterBuilder->setConditionType('eq')
                ->setField('creator_id')
                ->setValue($this->userContext->getUserId())
                ->create();

            $this->getContext()->getDataProvider()->addFilter($filter);
        }
    }

    /**
     * Show the filter if the company customer can view purchase orders and has subordinates.
     *
     * @return bool
     * @throws LocalizedException
     */
    private function isVisible()
    {
        $customerId = $this->userContext->getUserId();

        if ($this->authorization->isAllowed(
            'Magento_PurchaseOrder::view_purchase_orders_for_subordinates'
        )) {
            $subCustomers = $this->companyStructure->getAllowedChildrenIds($customerId);
            if (!empty($subCustomers)) {
                return true;
            }
        };

        if ($this->authorization->isAllowed(
            'Magento_PurchaseOrder::view_purchase_orders_for_company'
        )) {
            $customerExtensionAttributes = $this->customerResource->getCustomerExtensionAttributes($customerId);
            if (isset($customerExtensionAttributes['company_id'])) {
                $companyCustomers = $this->customerResource->getCustomerIdsByCompanyId(
                    $customerExtensionAttributes['company_id']
                );
                if (is_array($companyCustomers) && count($companyCustomers) > 1) {
                    return true;
                }
            }
        };

        return false;
    }
}
