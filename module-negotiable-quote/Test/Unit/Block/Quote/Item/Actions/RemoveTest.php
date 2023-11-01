<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Block\Quote\Item\Actions;

use Magento\Company\Api\AuthorizationInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\NegotiableQuote\Block\Quote\Item\Actions\Remove;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Magento\NegotiableQuote\Block\Quote\Item\Actions\Remove class.
 */
class RemoveTest extends TestCase
{
    /**
     * @var PostHelper|MockObject
     */
    private $postDataHelper;

    /**
     * @var AuthorizationInterface|MockObject
     */
    private $authorization;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilder;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var AbstractItem|MockObject
     */
    private $item;

    /**
     * @var Remove
     */
    private $block;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->postDataHelper = $this->getMockBuilder(PostHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->authorization = $this->getMockBuilder(AuthorizationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->item = $this->getMockBuilder(AbstractItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->block = $objectManager->getObject(
            Remove::class,
            [
                'postDataHelper' => $this->postDataHelper,
                'authorization' => $this->authorization,
                '_urlBuilder' => $this->urlBuilder,
                '_request' => $this->request,
                'item' => $this->item,
            ]
        );
    }

    /**
     * Test getRemoveParams method.
     *
     * @return void
     */
    public function testGetRemoveParams()
    {
        $quoteId = 1;
        $itemId = 3;
        $this->request->expects($this->once())->method('getParam')->with('quote_id')->willReturn($quoteId);
        $this->item->expects($this->once())->method('getId')->willReturn($itemId);
        $this->urlBuilder->expects($this->once())
            ->method('getUrl')
            ->with(
                '*/*/itemDelete',
                [
                    'quote_id' => 1,
                    'quote_item_id' => 3
                ]
            )
            ->willReturn('http://test.com/itemDelete/quote_id/1/quote_item_id/3');
        $this->postDataHelper->expects($this->once())
            ->method('getPostData')
            ->with('http://test.com/itemDelete/quote_id/1/quote_item_id/3')
            ->willReturn('post_data');

        $this->assertSame('post_data', $this->block->getRemoveParams());
    }

    /**
     * Test isAllowedManage method.
     *
     * @param bool $isAllowed
     * @param bool $expectedResult
     * @return void
     * @dataProvider isAllowedManageDataProvider
     */
    public function testIsAllowedManage($isAllowed, $expectedResult)
    {
        $this->authorization->expects($this->once())
            ->method('isAllowed')
            ->with('Magento_NegotiableQuote::manage')
            ->willReturn($isAllowed);

        $this->assertEquals($expectedResult, $this->block->isAllowedManage());
    }

    /**
     * Data provider for isAllowedManage method.
     *
     * @return array
     */
    public function isAllowedManageDataProvider()
    {
        return [
            [true, true],
            [false, false]
        ];
    }
}
