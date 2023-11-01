<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Block\Adminhtml\Quote\View\Errors;

use Magento\AdvancedCheckout\Block\Adminhtml\Sku\Errors\Grid\ColumnSet\SkuErrors;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\LayoutInterface;
use Magento\NegotiableQuote\Block\Adminhtml\Quote\View\Errors\Grid;
use Magento\NegotiableQuote\Model\ResourceModel\Sku\Errors\Grid\Collection;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for \Magento\NegotiableQuote\Block\Adminhtml\Quote\View\Errors\Grid class.
 */
class GridTest extends TestCase
{
    /**
     * @var Grid
     */
    private $grid;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['has'])
            ->getMockForAbstractClass();

        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->grid = $objectManager->getObject(
            Grid::class,
            [
                '_request' => $this->request,
                'quoteRepository' => $this->quoteRepository,
            ]
        );
    }

    /**
     * Tests getPreparedCollection() method.
     *
     * @return void
     */
    public function testGetPreparedCollection()
    {
        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $layout = $this->getMockBuilder(LayoutInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $layout->expects($this->once())
            ->method('getChildName')
            ->willReturn('child');
        $child = $this->getMockBuilder(SkuErrors::class)
            ->disableOriginalConstructor()
            ->getMock();
        $layout->expects($this->once())
            ->method('getBlock')
            ->willReturn($child);
        $this->request->expects($this->once())
            ->method('getParam')
            ->willReturn(1);

        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerGroupId'])
            ->getMockForAbstractClass();
        $quote->expects($this->once())
            ->method('getCustomerGroupId')
            ->willReturn(2);
        $this->quoteRepository->expects($this->once())
            ->method('get')
            ->willReturn($quote);

        $this->grid->setCollection($collection);
        $this->grid->setLayout($layout);
        $this->grid->getPreparedCollection();
    }
}
