<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Plugin\Quote\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\NegotiableQuote\Plugin\Quote\Model\QuoteItemPlugin;
use Magento\NegotiableQuote\Plugin\Quote\Model\QuoteRecalculate;
use Magento\Quote\Model\Product\QuoteItemsCleanerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QuoteItemPluginTest extends TestCase
{
    /**
     * @var QuoteItemPlugin
     */
    private $model;

    /**
     * @var QuoteRecalculate|MockObject
     */
    private $quoteRecalculateMock;

    protected function setUp(): void
    {
        $this->quoteRecalculateMock = $this->getMockBuilder(QuoteRecalculate::class)
            ->disableOriginalConstructor()
            ->setMethods(['updateQuotesByProduct'])
            ->getMock();
        $this->model = new QuoteItemPlugin($this->quoteRecalculateMock);
    }

    public function testAroundExecute()
    {
        $subjectMock = $this->getMockBuilder(QuoteItemsCleanerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $closure = function () {
        };
        $productMock = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->quoteRecalculateMock->expects($this->once())
            ->method('updateQuotesByProduct')
            ->with($closure, $productMock);
        $this->model->aroundExecute($subjectMock, $closure, $productMock);
    }
}
