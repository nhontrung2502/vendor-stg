<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Plugin\Company\Model;

use Magento\Company\Model\Company\Structure;
use Magento\Customer\Model\ResourceModel\Customer\Collection as CustomerCollection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\NegotiableQuote\Plugin\Company\Model\StructurePlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Plugin to filter customer by existing in company structure
 */
class StructurePluginTest extends TestCase
{
    /**
     * @var CustomerCollectionFactory|MockObject
     */
    private $customerCollectionFactory;

    /**
     * @var StructurePlugin
     */
    private $structurePlugin;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->customerCollectionFactory = $this->createMock(CustomerCollectionFactory::class);
        $this->structurePlugin = new StructurePlugin($this->customerCollectionFactory);
    }

    /**
     * Test to clear not existing users in allowed ids of structures
     *
     * @return void
     */
    public function testAfterGetAllowedIds(): void
    {
        $customerIds = [1, 2, 3];
        $existingCustomerIds = [2, 3];
        $this->setUpFilterExistingCustomersMethod($customerIds, $existingCustomerIds);

        /** @var Structure|MockObject $subject */
        $subject = $this->createMock(Structure::class);

        $actualIds = $this->structurePlugin->afterGetAllowedIds($subject, ['users' => $customerIds]);
        $this->assertEquals(['users' => $existingCustomerIds], $actualIds);
    }

    /**
     * Test to clear not existing users in allowed children IDs of customer
     *
     * @return void
     */
    public function testAfterGetAllowedChildrenIds(): void
    {
        $customerIds = [1, 2, 3];
        $existingCustomerIds = [2, 3];
        $this->setUpFilterExistingCustomersMethod($customerIds, $existingCustomerIds);

        /** @var Structure|MockObject $subject */
        $subject = $this->createMock(Structure::class);
        $actualIds = $this->structurePlugin->afterGetAllowedChildrenIds($subject, $customerIds);
        $this->assertEquals($actualIds, $existingCustomerIds);
    }

    /**
     * @param array $customerIds
     * @param array $existingCustomerIds
     */
    private function setUpFilterExistingCustomersMethod(array $customerIds, array $existingCustomerIds): void
    {
        $idFieldName = 'IdFieldName';
        /** @var CustomerCollection|MockObject $collection */
        $collection = $this->createMock(CustomerCollection::class);
        $collection->method('getIdFieldName')->willReturn($idFieldName);
        $collection->expects($this->once())
            ->method('addFieldToFilter')
            ->with($idFieldName, ['in' => $customerIds]);
        $collection->method('getAllIds')->willReturn($existingCustomerIds);

        $this->customerCollectionFactory->method('create')->willReturn($collection);
    }
}
