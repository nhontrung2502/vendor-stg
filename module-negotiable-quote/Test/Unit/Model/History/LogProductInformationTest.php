<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\History;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\History\LogProductInformation;
use Magento\NegotiableQuote\Model\ProductOptionsProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit test for Magento\NegotiableQuote\Model\History\LogProductInformation class.
 */
class LogProductInformationTest extends TestCase
{
    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var ProductOptionsProviderInterface|MockObject
     */
    private $optionsProvider;

    /**
     * @var LogProductInformation
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->productRepository = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->optionsProvider = $this->getMockBuilder(
            ProductOptionsProviderInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->model = $objectManagerHelper->getObject(
            LogProductInformation::class,
            [
                'productRepository' => $this->productRepository,
                'logger' => $this->logger,
                'productOptionsProviders' => [$this->optionsProvider]
            ]
        );
    }

    /**
     * Test getProductName method.
     *
     * @return void
     */
    public function testGetProductName()
    {
        $sku = 'product-sku';
        $productName = 'Product Name';
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productRepository->expects($this->once())->method('get')->with($sku)->willReturn($product);
        $product->expects($this->once())->method('getName')->willReturn($productName);

        $this->assertEquals('Product Name', $this->model->getProductName($sku));
    }

    /**
     * Test getProductName method with NoSuchEntityException.
     *
     * @return void
     */
    public function testGetProductNameWithNoSuchEntityException()
    {
        $sku = 'product-sku';
        $exception = new NoSuchEntityException(__('No such entity.'));
        $this->productRepository->expects($this->once())->method('get')->with($sku)->willThrowException($exception);
        $this->logger->expects($this->once())->method('critical')->with($exception);

        $this->assertEquals('product-sku' . __(' - deleted'), $this->model->getProductName($sku));
    }

    /**
     * Test getProductName method with Exception.
     *
     * @return void
     */
    public function testGetProductNameWithException()
    {
        $sku = 'product-sku';
        $exception = new \Exception();
        $this->productRepository->expects($this->once())->method('get')->with($sku)->willThrowException($exception);
        $this->logger->expects($this->once())->method('critical')->with($exception);

        $this->assertEquals('product-sku', $this->model->getProductName($sku));
    }

    /**
     * Test getProductNameById method.
     *
     * @return void
     */
    public function testGetProductNameById()
    {
        $productId = 1;
        $productName = 'Product Name';
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productRepository->expects($this->once())->method('getById')->with($productId)->willReturn($product);
        $product->expects($this->once())->method('getName')->willReturn($productName);

        $this->assertEquals('Product Name', $this->model->getProductNameById($productId));
    }

    /**
     * Test getProductNameById method with NoSuchEntityException.
     *
     * @return void
     */
    public function testGetProductNameByIdWithNoSuchEntityException()
    {
        $productId = 1;
        $exception = new NoSuchEntityException(__('No such entity.'));
        $this->productRepository->expects($this->once())
            ->method('getById')
            ->with($productId)
            ->willThrowException($exception);
        $this->logger->expects($this->once())->method('critical')->with($exception);

        $this->assertEquals(__('Product with ID #%1 is deleted', 1), $this->model->getProductNameById($productId));
    }

    /**
     * Test getProductNameById method with Exception.
     *
     * @return void
     */
    public function testGetProductNameByIdWithException()
    {
        $productId = 1;
        $exception = new \Exception();
        $this->productRepository->expects($this->once())
            ->method('getById')
            ->with($productId)
            ->willThrowException($exception);
        $this->logger->expects($this->once())->method('critical')->with($exception);

        $this->assertEquals(1, $this->model->getProductNameById($productId));
    }

    /**
     * Test getProductAttributes method.
     *
     * @return void
     */
    public function testGetProductAttributes()
    {
        $productId = 1;
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productRepository->expects($this->once())->method('getById')->with($productId)->willReturn($product);
        $product->expects($this->once())->method('getTypeId')->willReturn('bundle');
        $this->optionsProvider->expects($this->once())->method('getProductType')->willReturn('bundle');
        $this->optionsProvider->expects($this->once())->method('getOptions')->with($product)->willReturn([]);

        $this->assertEquals([], $this->model->getProductAttributes($productId));
    }

    /**
     * Test getProductAttributes method with NoSuchEntityException.
     *
     * @return void
     */
    public function testGetProductAttributesWithNoSuchEntityException()
    {
        $productId = 1;
        $exception = new NoSuchEntityException(__('No such entity.'));
        $this->productRepository->expects($this->once())
            ->method('getById')
            ->with($productId)
            ->willThrowException($exception);
        $this->logger->expects($this->once())->method('critical')->with($exception);

        $this->assertEquals([], $this->model->getProductAttributes($productId));
    }

    /**
     * Test getProductAttributes method with Exception.
     *
     * @return void
     */
    public function testGetProductAttributesWithException()
    {
        $productId = 1;
        $exception = new \Exception();
        $this->productRepository->expects($this->once())
            ->method('getById')
            ->with($productId)
            ->willThrowException($exception);
        $this->logger->expects($this->once())->method('critical')->with($exception);

        $this->assertEquals([], $this->model->getProductAttributes($productId));
    }
}
