<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\ResourceModel\Sku\Errors\Grid;

use Magento\AdvancedCheckout\Model\Cart;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\CatalogInventory\Api\Data\StockStatusInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Data\Collection\EntityFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\ResourceModel\Sku\Errors\Grid\Collection;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    /**
     * @var ProductInterfaceFactory|MockObject
     */
    private $productFactoryMock;

    /**
     * Test for loadData() method
     */
    public function testLoadData()
    {
        $productId = '3';
        $websiteId = '1';
        $sku = 'my sku';
        $typeId = 'giftcard';

        $cart = $this->getCartMock($productId, $websiteId, $sku);
        $priceCurrencyMock = $this->getPriceCurrencyMock();
        $entity = $this->getEntityFactoryMock();
        $stockStatusMock = $this->getMockBuilder(StockStatusInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $registryMock = $this->getMockBuilder(StockRegistryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $registryMock->expects($this->any())
            ->method('getStockStatus')
            ->withAnyParameters()
            ->willReturn($stockStatusMock);
        $this->productFactoryMock =
            $this->createPartialMock(ProductInterfaceFactory::class, ['create']);
        $this->getProductMock($typeId);

        $objectManager = new ObjectManager($this);
        $collection = $objectManager->getObject(
            Collection::class,
            [
                'entityFactory' => $entity,
                'cart' => $cart,
                'productFactory' => $this->productFactoryMock,
                'priceCurrency' => $priceCurrencyMock,
                'stockRegistry' => $registryMock
            ]
        );
        $collection->loadData();

        foreach ($collection->getItems() as $item) {
            $product = $item->getProduct();
            if ($item->getCode() != 'failed_sku') {
                $this->assertEquals($typeId, $product->getTypeId());
                $this->assertEquals('10.00', $item->getPrice());
            }
        }
    }

    /**
     * Return cart mock instance
     *
     * @return MockObject|Cart
     */
    private function getCartMock($productId, $storeId, $sku)
    {
        $cartMock = $this->getMockBuilder(
            Cart::class
        )->disableOriginalConstructor()
            ->setMethods(
                ['getFailedItems', 'getStore']
            )->getMock();
        $cartMock->expects(
            $this->any()
        )->method(
            'getFailedItems'
        )->willReturn(
            [
                [
                    "item" => ["id" => $productId, "is_qty_disabled" => "false", "sku" => $sku, "qty" => "1"],
                    "code" => "failed_configure",
                    "orig_qty" => "7",
                ],
                [
                    "item" => ["sku" => 'invalid', "qty" => "1"],
                    "code" => "failed_sku",
                    "orig_qty" => "1"
                ],
            ]
        );
        $storeMock = $this->getStoreMock($storeId);
        $cartMock->expects($this->any())->method('getStore')->willReturn($storeMock);

        return $cartMock;
    }

    /**
     * Return store mock instance
     *
     * @return MockObject|Store
     */
    private function getStoreMock($websiteId)
    {
        $storeMock = $this->createMock(Store::class);
        $storeMock->expects($this->any())->method('getWebsiteId')->willReturn($websiteId);

        return $storeMock;
    }

    /**
     * Mock product instance
     *
     * @return void
     */
    private function getProductMock($typeId)
    {
        $productMock = $this->getMockForAbstractClass(
            ProductInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['load', 'setIsSalable', 'setCustomerGroupId', 'getData', 'getTierPrice', 'setData']
        );
        $productMock->expects($this->any())->method('load')->willReturnSelf();
        $productMock->expects($this->once())->method('getData')->with('tier_price')->willReturn(1);
        $productMock->expects($this->once())->method('getTierPrice')->willReturn('10.00');
        $productMock->expects($this->once())->method('getTypeId')->willReturn($typeId);
        $this->productFactoryMock->expects($this->any())->method('create')->willReturn($productMock);
    }

    /**
     * Return PriceCurrencyInterface mock instance
     *
     * @return MockObject|\Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private function getPriceCurrencyMock()
    {
        $priceCurrencyMock = $this->getMockBuilder(PriceCurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $priceCurrencyMock->expects($this->any())->method('format')->willReturnArgument(0);

        return $priceCurrencyMock;
    }

    /**
     * Return entityFactory mock instance
     *
     * @return MockObject|EntityFactory
     */
    private function getEntityFactoryMock()
    {
        $entityFactoryMock = $this->createMock(EntityFactory::class);

        return $entityFactoryMock;
    }
}
