<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\NegotiableQuote\Plugin\Company\Model;

use Magento\Company\Model\Company\Structure;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;

/**
 * Plugin to filter customer by existing in company structure
 */
class StructurePlugin
{
    /**
     * Size of chunk to filter by customer id
     */
    private const SIZE_OF_CHUNK = 10000;

    /**
     * @var CustomerCollectionFactory
     */
    private $customerCollectionFactory;

    /**
     * @param CustomerCollectionFactory $customerCollectionFactory
     */
    public function __construct(
        CustomerCollectionFactory $customerCollectionFactory
    ) {
        $this->customerCollectionFactory = $customerCollectionFactory;
    }

    /**
     * Clear not existing users
     *
     * @param Structure $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetAllowedIds(
        Structure $subject,
        array $result
    ) {
        if (!empty($result['users'])) {
            $result['users'] = $this->filterExistingCustomers($result['users']);
        }

        return $result;
    }

    /**
     * Clear not existing company members
     *
     * @param Structure $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetAllowedChildrenIds(
        Structure $subject,
        array $result
    ) {
        return $this->filterExistingCustomers($result);
    }

    /**
     * Filter all existing customers
     *
     * @param array $allChildrenIds
     * @return array
     */
    private function filterExistingCustomers(array $allChildrenIds): array
    {
        $allIds = [];
        foreach (array_chunk($allChildrenIds, self::SIZE_OF_CHUNK) as $chunkOfIds) {
            $collection = $this->customerCollectionFactory->create();
            $collection->addFieldToFilter($collection->getIdFieldName(), ['in' => $chunkOfIds]);
            array_push($allIds, ...$collection->getAllIds());
        }

        return $allIds;
    }
}
