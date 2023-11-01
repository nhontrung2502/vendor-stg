<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PurchaseOrder\Test\Unit\Plugin\Quote\Model;

use Magento\Quote\Model\Quote;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\PurchaseOrder\Plugin\Quote\Model\QuoteRepositoryPlugin;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for QuoteRepositoryPlugin.
 */
class QuoteRepositoryPluginTest extends TestCase
{
    /**
     * @var Quote|MockObject
     */
    private $quote;

    /**
     * @var PurchaseOrderRepositoryInterface|MockObject
     */
    private $purchaseOrderRepository;

    /**
     * @var PurchaseOrderInterface|MockObject
     */
    private $purchaseOrder;

    /**
     * @var QuoteRepositoryPlugin
     */
    private $quoteRepositoryPlugin;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $cartRepository;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['setTotalsCollectedFlag', 'getData'])
            ->getMock();

        $this->purchaseOrder = $this->getMockBuilder(PurchaseOrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->purchaseOrderRepository = $this->getMockBuilder(PurchaseOrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cartRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);

        $this->quoteRepositoryPlugin = $objectManager->getObject(
            QuoteRepositoryPlugin::class,
            [
                'purchaseOrderRepository' => $this->purchaseOrderRepository,
            ]
        );
    }

    /**
     * Test for aroundGetActive method.
     *
     * @return void
     */
    public function testAroundGetActive()
    {
        $this->purchaseOrder->expects($this->once())->method('getEntityId')->willReturn(1);
        $this->purchaseOrderRepository->expects($this->once())
            ->method('getByQuoteId')
            ->willReturn($this->purchaseOrder);
        $this->cartRepository->expects($this->once())
            ->method('get')
            ->willReturn($this->quote);
        $this->quote->expects($this->once())->method('getData')->willReturn('0');
        $this->quote->expects($this->once())->method('setTotalsCollectedFlag')->willReturnSelf();
        $closure = function () {
            return $this->quote;
        };
        $this->quoteRepositoryPlugin->aroundGetActive($this->cartRepository, $closure, $this->quote->getId());
    }
}
