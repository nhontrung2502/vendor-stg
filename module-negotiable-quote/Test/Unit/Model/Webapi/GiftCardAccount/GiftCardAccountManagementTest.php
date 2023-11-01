<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Webapi\GiftCardAccount;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface;
use Magento\GiftCardAccount\Api\GiftCardAccountManagementInterface;
use Magento\NegotiableQuote\Model\Webapi\CustomerCartValidator;
use Magento\NegotiableQuote\Model\Webapi\GiftCardAccount\GiftCardAccountManagement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GiftCardAccountManagementTest extends TestCase
{
    /**
     * @var GiftCardAccountManagementInterface|MockObject
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
    private $giftCartCode = 'gift_card_code';

    /**
     * @var GiftCardAccountManagement|PHPUnitFrameworkMockObjectMockObject
     */
    private $giftCardAccountManagement;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->originalInterface =
            $this->getMockForAbstractClass(GiftCardAccountManagementInterface::class);
        $this->validator = $this->createMock(CustomerCartValidator::class);
        $objectManager = new ObjectManager($this);
        $this->giftCardAccountManagement = $objectManager->getObject(
            GiftCardAccountManagement::class,
            [
                'originalInterface' => $this->originalInterface,
                'validator' => $this->validator
            ]
        );
    }

    /**
     * Test deleteQuoteById
     */
    public function testDeleteByQuoteId()
    {
        $this->validator->expects($this->any())->method('validate')->willReturn(null);
        $this->originalInterface->expects($this->any())->method('deleteByQuoteId')->willReturn(true);

        $this->assertTrue(
            $this->giftCardAccountManagement->deleteByQuoteId($this->cartId, $this->giftCartCode)
        );
    }

    /**
     * Test saveByQuoteId
     */
    public function testSaveByQuoteId()
    {
        $this->validator->expects($this->any())->method('validate')->willReturn(null);
        $this->originalInterface->expects($this->any())->method('saveByQuoteId')->willReturn(true);
        /**
         * @var GiftCardAccountInterface $giftCardAccountData
         */
        $giftCardAccountData = $this->getMockForAbstractClass(GiftCardAccountInterface::class);

        $this->assertTrue($this->giftCardAccountManagement->saveByQuoteId($this->cartId, $giftCardAccountData));
    }
}
