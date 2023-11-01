<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Customer;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Model\ResourceModel\Address\Collection;
use Magento\Customer\Model\ResourceModel\Address\CollectionFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\Customer\RecalculationStatus;
use Magento\Tax\Helper\Data;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\NegotiableQuote\Model\Plugin\Customer\Model\RecalculateStatus class.
 */
class RecalculationStatusTest extends TestCase
{
    /**
     * @var Data|MockObject
     */
    private $taxHelper;

    /**
     * @var CollectionFactory|MockObject
     */
    protected $addressCollectionFactory;

    /**
     * @var RecalculationStatus
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->taxHelper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->addressCollectionFactory = $this->getMockBuilder(
            CollectionFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            RecalculationStatus::class,
            [
                'taxHelper' => $this->taxHelper,
                'addressCollectionFactory' => $this->addressCollectionFactory,
            ]
        );
    }

    /**
     * Test isNeedRecalculate method.
     *
     * @return void
     */
    public function testIsNeedRecalculate()
    {
        $addressId = 5;
        $address = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $oldAddress = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $addressCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->taxHelper->expects($this->once())->method('getTaxBasedOn')->willReturn('origin');
        $this->addressCollectionFactory->expects($this->once())->method('create')->willReturn($addressCollection);
        $address->expects($this->atLeastOnce())->method('getId')->willReturn($addressId);
        $addressCollection->expects($this->once())
            ->method('addFilter')
            ->with(AddressInterface::ID, $addressId)
            ->willReturnSelf();
        $addressCollection->expects($this->once())
            ->method('getItemById')
            ->with($addressId)
            ->willReturn($oldAddress);
        $oldAddress->expects($this->once())->method('getRegionId')->willReturn(2);
        $address->expects($this->once())->method('getRegionId')->willReturn(2);
        $oldAddress->expects($this->once())->method('getCountryId')->willReturn(2);
        $address->expects($this->once())->method('getCountryId')->willReturn(2);
        $oldAddress->expects($this->once())->method('getPostcode')->willReturn(220020);
        $address->expects($this->once())->method('getPostcode')->willReturn(223322);

        $this->assertTrue($this->model->isNeedRecalculate($address));
    }

    /**
     * Test isNeedRecalculate method.
     *
     * @return void
     */
    public function testIsNeedRecalculateEqualAddress()
    {
        $addressId = 5;
        $address = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $oldAddress = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $addressCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->taxHelper->expects($this->once())->method('getTaxBasedOn')->willReturn('origin');
        $this->addressCollectionFactory->expects($this->once())->method('create')->willReturn($addressCollection);
        $address->expects($this->atLeastOnce())->method('getId')->willReturn($addressId);
        $addressCollection->expects($this->once())
            ->method('addFilter')
            ->with(AddressInterface::ID, $addressId)
            ->willReturnSelf();
        $addressCollection->expects($this->once())
            ->method('getItemById')
            ->with($addressId)
            ->willReturn($oldAddress);
        $oldAddress->expects($this->once())->method('getRegionId')->willReturn(2);
        $address->expects($this->once())->method('getRegionId')->willReturn(2);
        $oldAddress->expects($this->once())->method('getCountryId')->willReturn(2);
        $address->expects($this->once())->method('getCountryId')->willReturn(2);
        $oldAddress->expects($this->once())->method('getPostcode')->willReturn(220020);
        $address->expects($this->once())->method('getPostcode')->willReturn(220020);

        $this->assertFalse($this->model->isNeedRecalculate($address));
    }
}
