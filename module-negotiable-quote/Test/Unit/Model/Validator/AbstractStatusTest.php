<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Validator;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\NegotiableQuote\Model\Validator\CheckoutStatus;
use Magento\NegotiableQuote\Model\Validator\ValidatorResult;
use Magento\NegotiableQuote\Model\Validator\ValidatorResultFactory;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Base class for validators unit tests.
 */
abstract class AbstractStatusTest extends TestCase
{
    /**
     * @var RestrictionInterface|MockObject
     */
    protected $restriction;

    /**
     * @var ValidatorResultFactory|MockObject
     */
    protected $validatorResultFactory;

    /**
     * @var CheckoutStatus
     */
    protected $status;

    /**
     * @var string
     */
    protected $statusClass;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->restriction = $this
            ->getMockBuilder(RestrictionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->validatorResultFactory = $this
            ->getMockBuilder(ValidatorResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->status = $objectManagerHelper->getObject(
            $this->statusClass,
            [
                'restriction' => $this->restriction,
                'validatorResultFactory' => $this->validatorResultFactory,
            ]
        );
    }

    /**
     * Prepare mocks for validate().
     *
     * @return void
     */
    protected function prepareMocksForValidate()
    {
        $result = $this->getMockBuilder(ValidatorResult::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validatorResultFactory->expects($this->atLeastOnce())->method('create')->willReturn($result);
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quote->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $this->data = ['quote' => $quote];

        $this->assertInstanceOf(
            ValidatorResult::class,
            $this->status->validate($this->data)
        );
    }

    /**
     * Prepare mocks for validate() with empty quote.
     *
     * @return void
     */
    protected function prepareMocksForValidateWithEmptyQuoteData()
    {
        $result = $this->getMockBuilder(ValidatorResult::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validatorResultFactory->expects($this->atLeastOnce())->method('create')->willReturn($result);
    }
}
