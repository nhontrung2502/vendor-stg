<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model;

use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use Magento\Catalog\Api\Data\ProductCustomOptionValuesInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\ProductOptionsProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test Magento\NegotiableQuote\Model\ProductOptionsProvider class.
 */
class ProductOptionsProviderTest extends TestCase
{
    /**
     * @var ProductOptionsProvider
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(ProductOptionsProvider::class, []);
    }

    /**
     * Test getProductType method.
     *
     * @return void
     */
    public function testGetProductType()
    {
        $productType = Type::TYPE_SIMPLE;

        $this->assertEquals($productType, $this->model->getProductType());
    }

    /**
     * Test getOptions method.
     *
     * @return void
     */
    public function testGetOptions()
    {
        $expectedResult = [
            1 => [
                'label' => 'Option Title',
                'values' => [
                    [
                        'value_index' => 1,
                        'label' => 'Value Title'
                    ]
                ]
            ]
        ];
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $optionInstance = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()
            ->getMock();
        $option = $this->getMockBuilder(ProductCustomOptionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $value = $this->getMockBuilder(ProductCustomOptionValuesInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product->expects($this->once())->method('getOptionInstance')->willReturn($optionInstance);
        $optionInstance->expects($this->once())
            ->method('getProductOptions')
            ->with($product)
            ->willReturn([$option]);
        $option->expects($this->atLeastOnce())->method('getOptionId')->willReturn(1);
        $option->expects($this->once())->method('getTitle')->willReturn('Option Title');
        $option->expects($this->atLeastOnce())->method('getValues')->willReturn([$value]);
        $value->expects($this->once())->method('getOptionTypeId')->willReturn(1);
        $value->expects($this->once())->method('getTitle')->willReturn('Value Title');

        $this->assertEquals($expectedResult, $this->model->getOptions($product));
    }

    /**
     * Test getOptions method for field without values.
     *
     * @return void
     */
    public function testGetOptionsWithoutValues()
    {
        $expectedResult = [
            1 => [
                'label' => 'Option Title',
                'values' => []
            ]
        ];
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $optionInstance = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()
            ->getMock();
        $option = $this->getMockBuilder(ProductCustomOptionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $product->expects($this->once())->method('getOptionInstance')->willReturn($optionInstance);
        $optionInstance->expects($this->once())
            ->method('getProductOptions')
            ->with($product)
            ->willReturn([$option]);
        $option->expects($this->atLeastOnce())->method('getOptionId')->willReturn(1);
        $option->expects($this->once())->method('getTitle')->willReturn('Option Title');
        $option->expects($this->atLeastOnce())->method('getValues')->willReturn(null);

        $this->assertEquals($expectedResult, $this->model->getOptions($product));
    }
}
