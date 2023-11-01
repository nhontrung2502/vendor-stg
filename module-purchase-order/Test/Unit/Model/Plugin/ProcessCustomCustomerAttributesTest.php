<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Test\Unit\Model\Plugin;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Api\ShippingInformationManagementInterface;
use Magento\Framework\Api\AttributeInterface;
use Magento\PurchaseOrder\Model\Plugin\ProcessCustomCustomerAttributes;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test process custom customer attributes before saving shipping address
 */
class ProcessCustomCustomerAttributesTest extends TestCase
{
    /**
     * @var ProcessCustomCustomerAttributes
     */
    protected $model;

    /**
     * @var ShippingInformationInterface| MockObject
     */
    protected $shippingInformationMock;

    /**
     * @var ShippingInformationManagementInterface| MockObject
     */
    protected $shippingInformationManagementMock;

    /**
     * @var AddressInterface| MockObject
     */
    protected $shippingAddressMock;

    /**
     * Prepare testable object
     */
    protected function setUp(): void
    {
        $this->shippingInformationManagementMock = $this->getMockForAbstractClass(
            ShippingInformationManagementInterface::class
        );
        $this->shippingInformationMock = $this->getMockForAbstractClass(
            ShippingInformationInterface::class
        );
        $this->shippingAddressMock = $this->getMockBuilder(
            Address::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['getCustomAttributes'])
            ->getMock();

        $this->model = new ProcessCustomCustomerAttributes();
    }

    /**
     * Test before save address information
     *
     * @dataProvider prepareDataSourceProvider
     * @param int $cartId
     * @param array $customAttributeArr1
     * @param array $customAttributeArr2
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testBeforeSaveAddressInformation(
        int $cartId,
        array $customAttributeArr1,
        array $customAttributeArr2
    ): void {
        $this->shippingInformationMock
            ->expects($this->once())
            ->method('getShippingAddress')
            ->willReturn($this->shippingAddressMock);
        $customAttribute1 = $this->getMockForAbstractClass(
            AttributeInterface::class
        );
        $customAttribute1
            ->expects($this->any())
            ->method('setAttributeCode')
            ->with($customAttributeArr1['code'])
            ->willReturnSelf();
        $customAttribute1
            ->expects($this->any())
            ->method('getAttributeCode')
            ->willReturn($customAttributeArr1['code']);
        $customAttribute1
            ->expects($this->any())
            ->method('getValue')
            ->willReturn($customAttributeArr1['value']);
        $customAttribute2 = $this->getMockForAbstractClass(
            AttributeInterface::class
        );
        $customAttribute2
            ->expects($this->any())
            ->method('setAttributeCode')
            ->with($customAttributeArr2['code'])
            ->willReturnSelf();
        $customAttribute2
            ->expects($this->any())
            ->method('getAttributeCode')
            ->willReturn($customAttributeArr2['code']);
        $customAttribute2
            ->expects($this->any())
            ->method('getValue')
            ->willReturn($customAttributeArr2['value']);
        $this->shippingAddressMock
            ->expects($this->any())
            ->method('getCustomAttributes')
            ->willReturn(
                [
                    $customAttribute1,
                    $customAttribute2
                ]
            );
        $this->model->beforeSaveAddressInformation(
            $this->shippingInformationManagementMock,
            $cartId,
            $this->shippingInformationMock
        );
    }

    /**
     * Data provider data source
     * @return array
     */
    public function prepareDataSourceProvider(): array
    {
        return [
            'with flat attribute code and value' => [
                42,
                [
                    'code' => 'test_attribute_1',
                    'value' =>  'test_value_1'
                ],
                [
                    'code' => 'test_attribute_2',
                    'value' =>  'test_value_2'
                ]
            ],
            'with attribute value as array' => [
                43,
                [
                    'code' => 'test_attribute_1',
                    'value' => [
                            'attribute_code' => 'test_attribute_1',
                            'value' => 'test_value_1'
                        ]
                ],
                [
                    'code' => 'test_attribute_2',
                    'value' => [
                            'attribute_code' => 'test_attribute_2',
                            'value' => 'test_value_2'
                        ]
                ]
            ]
        ];
    }
}
