<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Block\Adminhtml\Quote;

use Magento\Backend\Block\Widget\Button\ButtonList;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface as NegotiableQuote;
use Magento\NegotiableQuote\Block\Adminhtml\Quote\View;
use Magento\NegotiableQuote\Model\Restriction\Admin;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for View.
 */
class ViewTest extends TestCase
{
    /**
     * @var RestrictionInterface|MockObject
     */
    private $restriction;

    /**
     * @var View
     */
    private $view;

    /**
     * Test construct.
     *
     * @param string $status
     * @param bool $canSubmit
     * @param array $buttonsExpect
     * @return void
     * @dataProvider constructDataProvider
     */
    public function testConstruct($status, $canSubmit, $buttonsExpect)
    {
        $request = $this->getMockForAbstractClass(RequestInterface::class, [], '', false);
        $request->expects($this->atLeastOnce())->method('getParam')->willReturn(1);
        $buttonList = $this->getMockBuilder(ButtonList::class)
            ->disableOriginalConstructor()
            ->getMock();
        $buttons = [];
        $addButtonCallback = function ($buttonId, $data) use (&$buttons) {
            if (!isset($data['disabled']) || (isset($data['disabled']) && ($data['disabled'] === false))) {
                $buttons[] = $buttonId;
            }
        };
        $buttonList->expects($this->any())->method('add')->willReturnCallback($addButtonCallback);
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteNegotiation = $this->getMockBuilder(\Magento\NegotiableQuote\Model\NegotiableQuote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteNegotiation->expects($this->any())->method('getStatus')->willReturn($status);
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();
        $extensionAttributes->expects($this->any())->method('getNegotiableQuote')
            ->willReturn($quoteNegotiation);
        $quote->expects($this->any())->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        $this->restriction = $this->getMockBuilder(Admin::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->restriction->expects($this->atLeastOnce())->method('canSubmit')->willReturn($canSubmit);
        $this->restriction->setQuote($quote);

        $authorization = $this->getMockBuilder(AuthorizationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $authorization->expects($this->any())->method('isAllowed')->willReturn(true);

        $objectManager = new ObjectManager($this);
        $this->view = $objectManager->getObject(
            View::class,
            [
                'authorization' => $authorization,
                'restriction' => $this->restriction,
                'data' => [],
                'buttonList' => $buttonList,
                '_request' => $request
            ]
        );

        $this->assertEquals($buttonsExpect, $buttons);
    }

    /**
     * DataProvider for testConstruct().
     *
     * @return array
     */
    public function constructDataProvider()
    {
        return [
            [
                NegotiableQuote::STATUS_CREATED,
                true,
                ['back', 'quote_print', 'quote_save', 'quote_decline', 'quote_send']
            ],
            [
                NegotiableQuote::STATUS_ORDERED,
                false,
                ['back', 'quote_print']
            ]
        ];
    }
}
