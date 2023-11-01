<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Restriction;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\NegotiableQuote\Model\Restriction\UserTypeRestriction;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for CompositeRestriction.
 */
class UserTypeRestrictionTest extends TestCase
{
    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var RestrictionInterface|MockObject
     */
    private $restriction;

    /**
     * @var RestrictionInterface[]|MockObject
     */
    private $restrictions;

    /**
     * @var UserTypeRestriction
     */
    private $compositeRestriction;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->userContext = $this->getMockBuilder(UserContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->prepareRestrictionsMock();
        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->compositeRestriction = $objectManagerHelper->getObject(
            UserTypeRestriction::class,
            [
                'userContext' => $this->userContext,
                'restrictions' => $this->restrictions,
            ]
        );
    }

    /**
     * Test for canSubmit().
     *
     * @param bool $canSubmit
     * @return void
     * @dataProvider paramDataProvider
     */
    public function testCanSubmit($canSubmit)
    {
        $this->restriction->expects($this->atLeastOnce())->method('canSubmit')->willReturn($canSubmit);

        $this->assertEquals($canSubmit, $this->compositeRestriction->canSubmit());
    }

    /**
     * Test for canDuplicate().
     *
     * @param bool $canDuplicate
     * @return void
     * @dataProvider paramDataProvider
     */
    public function testCanDuplicate($canDuplicate)
    {
        $this->restriction->expects($this->atLeastOnce())->method('canDuplicate')->willReturn($canDuplicate);

        $this->assertEquals($canDuplicate, $this->compositeRestriction->canDuplicate());
    }

    /**
     * Test for canClose().
     *
     * @param bool $canClose
     * @return void
     * @dataProvider paramDataProvider
     */
    public function testCanClose($canClose)
    {
        $this->restriction->expects($this->atLeastOnce())->method('canClose')->willReturn($canClose);

        $this->assertEquals($canClose, $this->compositeRestriction->canClose());
    }

    /**
     * Test for canProceedToCheckout().
     *
     * @param bool $canProceedToCheckout
     * @return void
     * @dataProvider paramDataProvider
     */
    public function testCanProceedToCheckout($canProceedToCheckout)
    {
        $this->restriction->expects($this->atLeastOnce())->method('canProceedToCheckout')
            ->willReturn($canProceedToCheckout);

        $this->assertEquals($canProceedToCheckout, $this->compositeRestriction->canProceedToCheckout());
    }

    /**
     * Test for canDelete().
     *
     * @param bool $canDelete
     * @return void
     * @dataProvider paramDataProvider
     */
    public function testCanDelete($canDelete)
    {
        $this->restriction->expects($this->atLeastOnce())->method('canDelete')->willReturn($canDelete);

        $this->assertEquals($canDelete, $this->compositeRestriction->canDelete());
    }

    /**
     * Test for canDecline().
     *
     * @param bool $canDecline
     * @return void
     * @dataProvider paramDataProvider
     */
    public function testCanDecline($canDecline)
    {
        $this->restriction->expects($this->atLeastOnce())->method('canDecline')->willReturn($canDecline);

        $this->assertEquals($canDecline, $this->compositeRestriction->canDecline());
    }

    /**
     * Test for canCurrencyUpdate().
     *
     * @param bool $canCurrencyUpdate
     * @return void
     * @dataProvider paramDataProvider
     */
    public function testCanCurrencyUpdate($canCurrencyUpdate)
    {
        $this->restriction->expects($this->atLeastOnce())->method('canCurrencyUpdate')->willReturn($canCurrencyUpdate);

        $this->assertEquals($canCurrencyUpdate, $this->compositeRestriction->canCurrencyUpdate());
    }

    /**
     * Test for isLockMessageDisplayed().
     *
     * @param bool $isLockMessageDisplayed
     * @return void
     * @dataProvider paramDataProvider
     */
    public function testIsLockMessageDisplayed($isLockMessageDisplayed)
    {
        $this->restriction->expects($this->atLeastOnce())->method('isLockMessageDisplayed')
            ->willReturn($isLockMessageDisplayed);

        $this->assertEquals($isLockMessageDisplayed, $this->compositeRestriction->isLockMessageDisplayed());
    }

    /**
     * Test for isExpiredMessageDisplayed().
     *
     * @param bool $isExpiredMessageDisplayed
     * @return void
     * @dataProvider paramDataProvider
     */
    public function testIsExpiredMessageDisplayed($isExpiredMessageDisplayed)
    {
        $this->restriction->expects($this->atLeastOnce())->method('isExpiredMessageDisplayed')
            ->willReturn($isExpiredMessageDisplayed);

        $this->assertEquals($isExpiredMessageDisplayed, $this->compositeRestriction->isExpiredMessageDisplayed());
    }

    /**
     * Test for isOwner().
     *
     * @param bool $isOwner
     * @return void
     * @dataProvider paramDataProvider
     */
    public function testIsOwner($isOwner)
    {
        $this->restriction->expects($this->atLeastOnce())->method('isOwner')->willReturn($isOwner);

        $this->assertEquals($isOwner, $this->compositeRestriction->isOwner());
    }

    /**
     * Test for isSubUserContent().
     *
     * @param bool $isSubUserContent
     * @return void
     * @dataProvider paramDataProvider
     */
    public function testIsSubUserContent($isSubUserContent)
    {
        $this->restriction->expects($this->atLeastOnce())->method('isSubUserContent')->willReturn($isSubUserContent);

        $this->assertEquals($isSubUserContent, $this->compositeRestriction->isSubUserContent());
    }

    /**
     * Test for setQuote().
     *
     * @return void
     */
    public function testSetQuote()
    {
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->restriction->expects($this->atLeastOnce())->method('setQuote')->with($quote)->willReturnSelf();

        $this->assertInstanceOf(
            UserTypeRestriction::class,
            $this->compositeRestriction->setQuote($quote)
        );
    }

    /**
     * Test for isAllowed().
     *
     * @param bool $isAllowed
     * @return void
     * @dataProvider paramDataProvider
     */
    public function testIsAllowed($isAllowed)
    {
        $resource = 'resource';
        $this->restriction->expects($this->atLeastOnce())->method('isAllowed')->with($resource)->willReturn($isAllowed);

        $this->assertEquals($isAllowed, $this->compositeRestriction->isAllowed($resource));
    }

    /**
     * Test for isExtensionEnable().
     *
     * @param bool $isExtensionEnable
     * @return void
     * @dataProvider paramDataProvider
     */
    public function testIsExtensionEnable($isExtensionEnable)
    {
        $this->restriction->expects($this->atLeastOnce())->method('isExtensionEnable')->willReturn($isExtensionEnable);

        $this->assertEquals($isExtensionEnable, $this->compositeRestriction->isExtensionEnable());
    }

    /**
     * Prepare mock for restrictions.
     *
     * @return void
     */
    private function prepareRestrictionsMock()
    {
        $userType = 'user';
        $this->restriction = $this
            ->getMockBuilder(RestrictionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->userContext->expects($this->atLeastOnce())->method('getUserType')->willReturn($userType);
        $this->restrictions = [$userType => $this->restriction];
    }

    /**
     * Provides param for unit test methods.
     *
     * @return array
     */
    public function paramDataProvider()
    {
        return [
            [true],
            [false]
        ];
    }
}
