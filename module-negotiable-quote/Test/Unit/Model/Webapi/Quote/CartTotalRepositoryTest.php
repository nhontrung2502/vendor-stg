<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Webapi\Quote;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\Webapi\CustomerCartValidator;
use Magento\NegotiableQuote\Model\Webapi\Quote\CartTotalRepository;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\TotalsInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CartTotalRepositoryTest extends TestCase
{
    /**
     * @var CartTotalRepositoryInterface|MockObject
     */
    private $originalInterface;

    /**
     * @var CustomerCartValidator|MockObject
     */
    private $validator;

    /**
     * @var int
     */
    private $cartId = 1;

    /**
     * @var CartTotalRepository|MockObject
     */
    private $cartTotalRepository;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->originalInterface = $this->getMockForAbstractClass(CartTotalRepositoryInterface::class);
        $this->validator = $this->createMock(CustomerCartValidator::class);
        $objectManager = new ObjectManager($this);
        $this->cartTotalRepository = $objectManager->getObject(
            CartTotalRepository::class,
            [
                'originalInterface' => $this->originalInterface,
                'validator' => $this->validator
            ]
        );
    }

    /**
     * Test get
     */
    public function testGet()
    {
        $this->validator->expects($this->any())->method('validate')->willReturn(null);
        /**
         * @var TotalsInterface $totals
         */
        $totals = $this->getMockForAbstractClass(TotalsInterface::class);
        $this->originalInterface->expects($this->any())->method('get')->willReturn($totals);

        $this->assertEquals($totals, $this->cartTotalRepository->get($this->cartId));
    }
}
