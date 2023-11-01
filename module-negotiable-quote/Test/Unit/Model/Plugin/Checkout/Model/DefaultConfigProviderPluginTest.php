<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Plugin\Checkout\Model;

use Magento\Checkout\Model\DefaultConfigProvider;
use Magento\Framework\App\Action\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\NegotiableQuote\Model\Plugin\Checkout\Model\DefaultConfigProviderPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DefaultConfigProviderPluginTest extends TestCase
{
    /**
     * @var UrlInterface|MockObject
     */
    protected $urlBuilder;

    /**
     * @var Context|MockObject
     */
    protected $context;

    /**
     * @var DefaultConfigProviderPlugin
     */
    protected $plugin;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->context = $objectManager->getObject(Context::class);
        $this->urlBuilder = $this->context->getUrl();
        $this->urlBuilder->expects($this->any())->method('getUrl')->willReturnArgument(0);
        $this->plugin = $objectManager->getObject(
            DefaultConfigProviderPlugin::class,
            [
                'context' => $this->context,
                'urlBuilder' => $this->urlBuilder,
            ]
        );
    }

    /**
     * Test afterGetCheckoutUrl()
     *
     * @dataProvider afterGetCheckoutUrlDataProvider
     *
     * @param int $id
     * @param string $expectedResult
     */
    public function testAfterGetCheckoutUrl($id, $expectedResult)
    {
        $this->context->getRequest()->expects($this->any())->method('getParam')->willReturn($id);
        $subject = $this->createMock(DefaultConfigProvider::class);

        $this->assertEquals($expectedResult, $this->plugin->afterGetCheckoutUrl($subject, 'url'));
    }

    /**
     * @return array
     */
    public function afterGetCheckoutUrlDataProvider()
    {
        return [
            [0, 'url'],
            [2, 'checkout']
        ];
    }

    /**
     * Test afterGetDefaultSuccessPageUrl()
     *
     * @dataProvider afterGetDefaultSuccessPageUrlDataProvider
     *
     * @param int $id
     * @param string $expectedResult
     */
    public function testAfterGetDefaultSuccessPageUrl($id, $expectedResult)
    {
        $this->context->getRequest()->expects($this->any())->method('getParam')->willReturn($id);
        $subject = $this->createMock(DefaultConfigProvider::class);

        $this->assertEquals($expectedResult, $this->plugin->afterGetDefaultSuccessPageUrl($subject, 'url'));
    }

    /**
     * @return array
     */
    public function afterGetDefaultSuccessPageUrlDataProvider()
    {
        return [
            [0, 'url'],
            [2, 'negotiable_quote/quote/order']
        ];
    }
}
