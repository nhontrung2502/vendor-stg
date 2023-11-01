<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Discount\StateChanges;

use Magento\Framework\App\Area;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Model\Discount\StateChanges\Applier;
use Magento\NegotiableQuote\Model\Discount\StateChanges\Provider;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\NegotiableQuote\Model\Status\LabelProviderInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProviderTest extends TestCase
{
    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var State|MockObject
     */
    private $appState;

    /**
     * @var LabelProviderInterface|MockObject
     */
    private $labelProvider;

    /**
     * @var Applier|MockObject
     */
    private $messageApplier;

    /**
     * @var RestrictionInterface|MockObject
     */
    private $restriction;

    /**
     * @var CartInterface|MockObject
     */
    private $quote;

    /**
     * @var NegotiableQuoteInterface|MockObject
     */
    private $negotiableQuote;

    /**
     * @var Provider|MockObject
     */
    private $provider;

    /**
     * @var Json|MockObject
     */
    private $serializerMock;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->request = $this->getMockForAbstractClass(RequestInterface::class);
        $this->appState = $this->createMock(State::class);
        $this->labelProvider = $this->getMockForAbstractClass(LabelProviderInterface::class);
        $this->messageApplier = $this->createMock(Applier::class);
        $this->restriction = $this->getMockForAbstractClass(RestrictionInterface::class);
        $this->quote = $this->getMockForAbstractClass(CartInterface::class);
        $this->negotiableQuote = $this->getMockForAbstractClass(NegotiableQuoteInterface::class);
        $this->serializerMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['serialize', 'unserialize'])
            ->getMock();

        $this->serializerMock->expects($this->any())
            ->method('serialize')
            ->willReturnCallback(
                function ($value) {
                    return json_encode($value);
                }
            );

        $this->serializerMock->expects($this->any())
            ->method('unserialize')
            ->willReturnCallback(
                function ($value) {
                    return json_decode($value, true);
                }
            );

        $objectManager = new ObjectManager($this);
        $this->provider = $objectManager->getObject(
            Provider::class,
            [
                'request' => $this->request,
                'appState' => $this->appState,
                'labelProvider' => $this->labelProvider,
                'messageApplier' => $this->messageApplier,
                'restriction' => $this->restriction,
                'serializer' => $this->serializerMock
            ]
        );
    }

    /**
     * Test hasChanges
     *
     * @param int $notificationsHasItemChanges
     * @param int $notificationsIsDiscountChanged
     * @param int $notificationsIsDiscountRemovedLimit
     * @param int $notificationsIsDiscountRemoved
     * @param string $areaCode
     * @param bool $hasChanges
     * @dataProvider dataProviderHasChanges
     */
    public function testHasChanges(
        $notificationsHasItemChanges,
        $notificationsIsDiscountChanged,
        $notificationsIsDiscountRemovedLimit,
        $notificationsIsDiscountRemoved,
        $areaCode,
        $hasChanges
    ) {
        $this->hasDiscountChangesMultipleNotifications(
            $notificationsHasItemChanges,
            $notificationsIsDiscountChanged,
            $notificationsIsDiscountRemovedLimit,
            $notificationsIsDiscountRemoved,
            $areaCode
        );

        $this->assertEquals($hasChanges, $this->provider->hasChanges($this->quote));
    }

    /**
     * Test hasItemChanges
     *
     * @param int $notifications
     * @param string $areaCode
     * @param bool $hasChanges
     * @dataProvider dataProviderHasItemChanges
     */
    public function testHasItemChanges($notifications, $areaCode, $hasChanges)
    {
        $this->hasDiscountChanges($notifications, $areaCode);

        $this->assertEquals($hasChanges, $this->provider->hasItemChanges($this->quote));
    }

    /**
     * Test isDiscountChanged
     *
     * @param int $notifications
     * @param string $areaCode
     * @param bool $hasChanges
     * @dataProvider dataProviderIsDiscountChanged
     */
    public function testIsDiscountChanged($notifications, $areaCode, $hasChanges)
    {
        $this->hasDiscountChanges($notifications, $areaCode);

        $this->assertEquals($hasChanges, $this->provider->isDiscountChanged($this->quote));
    }

    /**
     * Test isDiscountRemovedLimit
     *
     * @param int $notifications
     * @param string $areaCode
     * @param bool $hasChanges
     * @dataProvider dataProviderIsDiscountRemovedLimit
     */
    public function testIsDiscountRemovedLimit($notifications, $areaCode, $hasChanges)
    {
        $this->hasDiscountChanges($notifications, $areaCode);

        $this->assertEquals($hasChanges, $this->provider->isDiscountRemovedLimit($this->quote));
    }

    /**
     * Test isDiscountRemovedLimit
     *
     * @param int $notifications
     * @param string $areaCode
     * @param bool $hasChanges
     * @dataProvider dataProviderIsDiscountRemoved
     */
    public function testIsDiscountRemoved($notifications, $areaCode, $hasChanges)
    {
        $this->hasDiscountChanges($notifications, $areaCode);

        $this->assertEquals($hasChanges, $this->provider->isDiscountRemoved($this->quote));
    }

    /**
     * Test hasDiscountChanges
     *
     * @param int $notifications
     * @param int $type
     * @param string $areaCode
     * @param bool $discountModificationType
     * @dataProvider dataProviderHasDiscountChanges
     */
    public function testHasDiscountChanges($notifications, $type, $areaCode, $discountModificationType)
    {
        $this->hasDiscountChanges($notifications, $areaCode);

        $this->assertEquals($discountModificationType, $this->provider->hasDiscountChanges($this->quote, $type));
    }

    /**
     * Test getChangesMessages
     *
     * @param int $notifications
     * @param string $areaCode
     * @param bool $canSubmit
     * @param string $status
     * @param string $deletedSku
     * @param array $messages
     * @dataProvider dataProviderGetChangesMessages
     */
    public function testGetChangesMessages($notifications, $areaCode, $canSubmit, $status, $deletedSku, array $messages)
    {
        $this->getChangesMessages($notifications, $areaCode, $canSubmit, $status, $deletedSku);

        $this->assertEquals($messages, $this->provider->getChangesMessages($this->quote));
    }

    public function testGetChangesMessagesWithEmptyNegotiableQuote()
    {
        $extensionAttributes = $this->getMockForAbstractClass(
            CartExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getNegotiableQuote']
        );
        $extensionAttributes->expects($this->any())->method('getNegotiableQuote')->willReturn(null);
        $this->quote->expects($this->any())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $messages = [];

        $this->assertEquals($messages, $this->provider->getChangesMessages($this->quote));
    }

    /**
     * DataProvider hasItemChanges
     *
     * @return array
     */
    public function dataProviderHasChanges()
    {
        return [
            [0, 1, 1, 1, '', false],
            [1, 2 ,4 ,8, '', true],
            [1, 1, 4 ,8, '', true],
            [1, 2, 1, 8, '', true],
            [1, 2, 4, 1, '', true],
            [1, 2, 4, 8, Area::AREA_ADMINHTML, false]
        ];
    }

    /**
     * DataProvider hasItemChanges
     *
     * @return array
     */
    public function dataProviderHasItemChanges()
    {
        return [
            [1, Area::AREA_ADMINHTML, false],
            [1, '', true],
            [3, '', true],
            [2, '', false],
            [2, Area::AREA_ADMINHTML, false]
        ];
    }

    /**
     * DataProvider isDiscountChanged
     *
     * @return array
     */
    public function dataProviderIsDiscountChanged()
    {
        return [
            [1, Area::AREA_ADMINHTML, false],
            [1, '', false],
            [2, '', true],
            [6, '', true],
            [2, Area::AREA_ADMINHTML, false]
        ];
    }

    /**
     * DataProvider isDiscountRemovedLimit
     *
     * @return array
     */
    public function dataProviderIsDiscountRemovedLimit()
    {
        return [
            [1, Area::AREA_ADMINHTML, false],
            [1, '', false],
            [4, '', true],
            [5, '', true],
            [2, Area::AREA_ADMINHTML, false]
        ];
    }

    /**
     * DataProvider isDiscountRemoved
     *
     * @return array
     */
    public function dataProviderIsDiscountRemoved()
    {
        return [
            [1, Area::AREA_ADMINHTML, false],
            [1, '', false],
            [8, '', true],
            [9, '', true],
            [2, Area::AREA_ADMINHTML, false]
        ];
    }

    /**
     * DataProvider hasDiscountChanges
     *
     * @return array
     */
    public function dataProviderHasDiscountChanges()
    {
        return [
            [1, 2, Area::AREA_ADMINHTML, false],
            [1, 1, '', true],
            [2, 2, '', true],
            [1, 5, '', false],
            [0, 2, Area::AREA_ADMINHTML, false],
            [0, 2, '', false]
        ];
    }

    /**
     * DataProvider getChangesMessages
     *
     * @return array
     */
    public function dataProviderGetChangesMessages()
    {
        $deletedSku = '{"state": {"a": 2}}';
        $emptyDeletedSku = '{}';

        return [
            [1, 'state', true, '', $deletedSku, ['message 1', 'message 2']],
            [1, '', true, '', $emptyDeletedSku, ['message 1']],
            [
                1,
                '',
                true,
                NegotiableQuoteInterface::STATUS_EXPIRED,
                $emptyDeletedSku,
                []
            ],
            [0, '', true, '', $emptyDeletedSku, []],
            [0, '', false, '', $emptyDeletedSku, []]
        ];
    }

    /**
     * HasDiscountChanges
     *
     * @param int $notifications
     * @param string $areaCode
     */
    private function hasDiscountChanges($notifications, $areaCode)
    {
        $extensionAttributes = $this->getMockForAbstractClass(
            CartExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getNegotiableQuote']
        );
        $this->negotiableQuote->expects($this->any())->method('getNotifications')->willReturn($notifications);
        $extensionAttributes->expects($this->any())->method('getNegotiableQuote')->willReturn($this->negotiableQuote);
        $this->quote->expects($this->any())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $this->appState->expects($this->any())->method('getAreaCode')
            ->willReturn($areaCode);
    }

    /**
     * HasDiscountChangesMultipleNotifications
     *
     * @param int $notificationsHasItemChanges
     * @param int $notificationsIsDiscountChanged
     * @param int $notificationsIsDiscountRemovedLimit
     * @param int $notificationsIsDiscountRemoved
     * @param string $areaCode
     */
    private function hasDiscountChangesMultipleNotifications(
        $notificationsHasItemChanges,
        $notificationsIsDiscountChanged,
        $notificationsIsDiscountRemovedLimit,
        $notificationsIsDiscountRemoved,
        $areaCode
    ) {
        $extensionAttributes = $this->getMockForAbstractClass(
            CartExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getNegotiableQuote']
        );
        $this->negotiableQuote->expects($this->any())->method('getNotifications')
            ->willReturnOnConsecutiveCalls(
                $notificationsHasItemChanges,
                $notificationsIsDiscountChanged,
                $notificationsIsDiscountRemovedLimit,
                $notificationsIsDiscountRemoved
            );
        $extensionAttributes->expects($this->any())->method('getNegotiableQuote')->willReturn($this->negotiableQuote);
        $this->quote->expects($this->any())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $this->appState->expects($this->any())->method('getAreaCode')->willReturn($areaCode);
    }

    /**
     * getChangesMessages
     *
     * @param int $notifications
     * @param string $areaCode
     * @param bool $canSubmit
     * @param string $status
     * @param string $deletedSku
     */
    private function getChangesMessages($notifications, $areaCode, $canSubmit, $status, $deletedSku)
    {
        $this->restriction->expects($this->any())->method('canSubmit')->willReturn($canSubmit);
        $this->hasDiscountChanges($notifications, $areaCode);
        $messages = [
            '1' => 'message 1',
            '2' => 'message 2'
        ];
        $this->labelProvider->expects($this->any())->method('getMessageLabels')->willReturn($messages);
        $this->labelProvider->expects($this->any())->method('getRemovedSkuMessage')->willReturn('message 2');
        $this->negotiableQuote->expects($this->any())->method('getStatus')->willReturn($status);
        $this->negotiableQuote->expects($this->any())->method('getDeletedSku')->willReturn($deletedSku);
    }
}
