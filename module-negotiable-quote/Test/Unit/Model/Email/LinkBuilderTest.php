<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Email;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\NegotiableQuote\Model\Email\LinkBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Magento\NegotiableQuote\Model\Email\LinkBuilder class.
 */
class LinkBuilderTest extends TestCase
{
    /**
     * @var UrlInterface|MockObject
     */
    private $frontendUrlBuilder;

    /**
     * @var \Magento\Backend\Model\UrlInterface|MockObject
     */
    private $backendUrlBuilder;

    /**
     * @var LinkBuilder
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->frontendUrlBuilder = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->backendUrlBuilder = $this->getMockBuilder(\Magento\Backend\Model\UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            LinkBuilder::class,
            [
                'frontendUrlBuilder' => $this->frontendUrlBuilder,
                'backendUrlBuilder' => $this->backendUrlBuilder,
            ]
        );
    }

    /**
     * Test getBackendUrl method.
     *
     * @return void
     */
    public function testGetBackendUrl()
    {
        $url = 'http://example.com/admin';
        $this->backendUrlBuilder->expects($this->once())->method('getUrl')->with(null, [])->willReturn($url);

        $this->assertSame($url, $this->model->getBackendUrl());
    }

    /**
     * Test getFrontendUrl method.
     *
     * @return void
     */
    public function testGetFrontendUrl()
    {
        $routePath = 'http://example.com/';
        $scope = 'website';
        $store = 'default';
        $quoteId = 1;
        $this->frontendUrlBuilder->expects($this->once())->method('setScope')->with($scope)->willReturnSelf();
        $this->frontendUrlBuilder->expects($this->once())
            ->method('getUrl')
            ->with(
                $routePath,
                [
                    'quote_id' => $quoteId,
                    '_current' => false,
                    '_query' =>'___store=default'
                ]
            )
            ->willReturn($routePath . 'quote_id/1');

        $this->assertSame(
            $routePath . 'quote_id/1',
            $this->model->getFrontendUrl($routePath, $scope, $store, $quoteId)
        );
    }
}
