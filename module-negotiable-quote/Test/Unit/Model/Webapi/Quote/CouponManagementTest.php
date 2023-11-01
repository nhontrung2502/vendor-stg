<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Webapi\Quote;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\Webapi\CustomerCartValidator;
use Magento\NegotiableQuote\Model\Webapi\Quote\CouponManagement;
use Magento\Quote\Api\CouponManagementInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CouponManagementTest extends TestCase
{
    /**
     * @var CouponManagementInterface|MockObject
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
     * @var string
     */
    private $couponCode = 'coupon_code';

    /**
     * @var CouponManagement|MockObject
     */
    private $couponManagement;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->originalInterface = $this->getMockForAbstractClass(CouponManagementInterface::class);
        $this->validator = $this->createMock(CustomerCartValidator::class);
        $objectManager = new ObjectManager($this);
        $this->couponManagement = $objectManager->getObject(
            CouponManagement::class,
            [
                'originalInterface' => $this->originalInterface,
                'validator' => $this->validator
            ]
        );
    }

    /**
     * Test set
     */
    public function testSet()
    {
        $this->validator->expects($this->any())->method('validate')->willReturn(null);
        $this->originalInterface->expects($this->any())->method('set')->willReturn(true);

        $this->assertTrue($this->couponManagement->set($this->cartId, $this->couponCode));
    }

    /**
     * Test remove
     */
    public function testRemove()
    {
        $this->validator->expects($this->any())->method('validate')->willReturn(null);
        $this->originalInterface->expects($this->any())->method('remove')->willReturn(true);

        $this->assertTrue($this->couponManagement->remove($this->cartId));
    }
}
