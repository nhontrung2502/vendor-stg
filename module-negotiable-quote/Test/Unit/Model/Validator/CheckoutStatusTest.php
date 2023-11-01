<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Validator;

use Magento\NegotiableQuote\Model\Validator\CheckoutStatus;
use Magento\NegotiableQuote\Model\Validator\ValidatorResult;

/**
 * Unit test for CheckoutStatus.
 */
class CheckoutStatusTest extends AbstractStatusTest
{
    /**
     * @var string
     */
    protected $statusClass = CheckoutStatus::class;

    /**
     * Test for validate().
     *
     * @return void
     */
    public function testValidate()
    {
        $this->prepareMocksForValidate();
        $this->restriction->expects($this->atLeastOnce())->method('canProceedToCheckout')->willReturn(false);

        $this->assertInstanceOf(
            ValidatorResult::class,
            $this->status->validate($this->data)
        );
    }

    /**
     * Test validate() with empty quote data.
     *
     * @return void
     */
    public function testValidateWithEmptyQuoteData()
    {
        $this->prepareMocksForValidateWithEmptyQuoteData();
        $this->restriction->expects($this->never())->method('canProceedToCheckout');

        $this->assertInstanceOf(
            ValidatorResult::class,
            $this->status->validate($this->data)
        );
    }
}
