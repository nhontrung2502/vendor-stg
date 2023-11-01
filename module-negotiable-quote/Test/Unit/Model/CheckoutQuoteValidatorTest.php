<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model;

use Magento\CatalogInventory\Helper\Data;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\CheckoutQuoteValidator;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CheckoutQuoteValidatorTest extends TestCase
{
    /**
     * @var CheckoutQuoteValidator
     */
    protected $checkoutQuoteValidator;

    /**
     * @var CartInterface|MockObject
     */
    protected $quoteMock;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->quoteMock = $this->getMockForAbstractClass(
            CartInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getAllVisibleItems']
        );

        $objectManager = new ObjectManager($this);
        $this->checkoutQuoteValidator = $objectManager->getObject(
            CheckoutQuoteValidator::class
        );
    }

    /**
     * Test of countInvalidQtyItems() method
     *
     * @param string $origin
     * @param int $expectErrorsCount
     * @dataProvider countInvalidQtyItemsDataProvider
     */
    public function testCountInvalidQtyItems($origin, $expectErrorsCount)
    {
        $quoteItemMock = $this->getMockForAbstractClass(
            CartItemInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getErrorInfos']
        );
        $errorInfos = [
            [
                'origin' => $origin,
                'code' => Data::ERROR_QTY
            ]
        ];
        $quoteItemMock->expects($this->once())
            ->method('getErrorInfos')
            ->willReturn($errorInfos);
        $this->quoteMock
            ->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$quoteItemMock]);

        $this->assertEquals($expectErrorsCount, $this->checkoutQuoteValidator->countInvalidQtyItems($this->quoteMock));
    }

    /**
     * Data Provider for testCountInvalidQtyItems()
     *
     * @return array
     */
    public function countInvalidQtyItemsDataProvider()
    {
        return [
            ['cataloginventory', 1],
            ['xxx', 0]
        ];
    }
}
