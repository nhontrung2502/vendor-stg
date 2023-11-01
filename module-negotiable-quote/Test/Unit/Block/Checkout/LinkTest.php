<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Block\Checkout;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Api\AuthorizationInterface;
use Magento\Framework\File\Size;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\NegotiableQuote\Block\Checkout\Link;
use Magento\NegotiableQuote\Helper\Config;
use Magento\NegotiableQuote\Helper\Quote;
use Magento\Quote\Api\CartManagementInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase
{
    /**
     * @var  Link
     */
    private $block;

    /**
     * @var  UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var  Config|MockObject
     */
    private $configHelper;

    /**
     * @var  CartManagementInterface|MockObject
     */
    private $cartManagement;

    /**
     * @var  Quote|MockObject
     */
    private $quoteHelper;

    /**
     * @var  Size|MockObject
     */
    private $fileSize;

    /**
     * @var  \Magento\NegotiableQuote\Model\Config|MockObject
     */
    private $negotiableQuoteConfig;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilder;

    /**
     * @var AuthorizationInterface|MockObject
     */
    private $authorization;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->userContext = $this->getMockForAbstractClass(
            UserContextInterface::class,
            [],
            '',
            false
        );

        $this->configHelper = $this->createMock(
            Config::class
        );

        $this->cartManagement = $this->getMockForAbstractClass(
            CartManagementInterface::class,
            [],
            '',
            false
        );

        $this->quoteHelper = $this->createMock(
            Quote::class
        );

        $this->fileSize = $this->createMock(
            Size::class
        );

        $this->negotiableQuoteConfig = $this->createMock(
            \Magento\NegotiableQuote\Model\Config::class
        );

        $this->authorization = $this->createMock(
            AuthorizationInterface::class
        );

        $this->urlBuilder = $this->getMockForAbstractClass(UrlInterface::class);

        $objectManager = new ObjectManager($this);
        $this->block = $objectManager->getObject(
            Link::class,
            [
                'userContext' => $this->userContext,
                'configHelper' => $this->configHelper,
                'cartManagement' => $this->cartManagement,
                'quoteHelper' => $this->quoteHelper,
                'fileSize' => $this->fileSize,
                'negotiableQuoteConfig' => $this->negotiableQuoteConfig,
                'authorization' => $this->authorization,
                'data' => [],
                '_urlBuilder' => $this->urlBuilder,
            ]
        );
    }

    /**
     * Test isQuoteRequestAllowed.
     *
     * @param bool $result
     * @return void
     * @dataProvider isQuoteRequestAllowedDataProvider
     */
    public function testIsQuoteRequestAllowed($result)
    {
        $userId = 1;
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($userId);

        $quote = $this->createMock(
            \Magento\Quote\Model\Quote::class
        );
        $this->cartManagement->expects($this->once())->method('getCartForCustomer')
            ->with($userId)->willReturn($quote);
        $this->configHelper->expects($this->once())->method('isQuoteAllowed')
            ->with($quote)->willReturn($result);
        $this->authorization->expects($this->any())->method('isAllowed')->willReturn($result);

        $this->assertEquals($result, $this->block->isQuoteRequestAllowed());
    }

    /**
     * DataProvider for isQuoteRequestAllowed.
     *
     * @return array
     */
    public function isQuoteRequestAllowedDataProvider()
    {
        return [
            [true], [false]
        ];
    }

    /**
     * Test for method getDisallowMessage.
     *
     * @return void
     */
    public function testGetDisallowMessage()
    {
        $string = '';
        $this->configHelper->expects($this->once())->method('getMinimumAmountMessage')
            ->willReturn($string);

        $this->assertEquals($string, $this->block->getDisallowMessage());
    }

    /**
     * Test for method getCreateNegotiableQuoteUrl.
     *
     * @return void
     */
    public function testGetCreateNegotiableQuoteUrl()
    {
        $path = 'negotiable_quote/quote/create';
        $url = 'http://example.com/';

        $this->urlBuilder->expects($this->once())->method('getUrl')
            ->willReturn($url . $path);
        $this->assertEquals($url . $path, $this->block->getCreateNegotiableQuoteUrl());
    }

    /**
     * Test for method getCheckQuoteDiscountsUrl.
     *
     * @return void
     */
    public function testGetCheckQuoteDiscountsUrl()
    {
        $path = 'negotiable_quote/quote/checkDiscount';
        $url = 'http://example.com/';

        $this->urlBuilder->expects($this->once())->method('getUrl')
            ->willReturn($url . $path);
        $this->assertEquals($url . $path, $this->block->getCreateNegotiableQuoteUrl());
    }

    /**
     * Test for method getMaxFileSize.
     *
     * @return void
     */
    public function testGetMaxFileSize()
    {
        $maxFileSize = 14;
        $this->fileSize->expects($this->once())->method('convertSizeToInteger')
            ->willReturn($maxFileSize);

        $this->assertEquals($maxFileSize, $this->block->getMaxFileSize());
    }

    /**
     * Test for method getAllowedExtensionsString.
     *
     * @return void
     */
    public function testGetAllowedExtensionsString()
    {
        $extensions = '.php.inc';
        $this->negotiableQuoteConfig->expects($this->once())->method('getAllowedExtensions')
            ->willReturn($extensions);

        $this->assertEquals($extensions, $this->block->getAllowedExtensions());
    }

    /**
     * Test for method getAllowedExtensionsNull.
     *
     * @return void
     */
    public function testGetAllowedExtensionsNull()
    {
        $extensions = null;
        $this->negotiableQuoteConfig->expects($this->once())->method('getAllowedExtensions')
            ->willReturn($extensions);

        $this->assertEquals($extensions, $this->block->getAllowedExtensions());
    }

    /**
     * Test for method getMaxFileSizeMbConfigSize.
     *
     * @return void
     */
    public function testGetMaxFileSizeMbConfigSize()
    {
        $maxFileSizeConfig = 10;
        $maxFileSize = 14;
        $this->negotiableQuoteConfig->expects($this->once())->method('getMaxFileSize')
            ->willReturn($maxFileSizeConfig);
        $this->fileSize->expects($this->once())->method('getMaxFileSizeInMb')
            ->willReturn($maxFileSize);

        $this->assertEquals($maxFileSizeConfig, $this->block->getMaxFileSizeMb());
    }

    /**
     * Test for method getMaxFileSizeMbFileSize.
     *
     * @return void
     */
    public function testGetMaxFileSizeMbFileSize()
    {
        $maxFileSizeConfig = null;
        $maxFileSize = 14;
        $this->negotiableQuoteConfig->expects($this->once())->method('getMaxFileSize')
            ->willReturn($maxFileSizeConfig);
        $this->fileSize->expects($this->once())->method('getMaxFileSizeInMb')
            ->willReturn($maxFileSize);

        $this->assertEquals($maxFileSize, $this->block->getMaxFileSizeMb());
    }
}
