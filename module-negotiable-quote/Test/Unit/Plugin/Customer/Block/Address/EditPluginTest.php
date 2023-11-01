<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Plugin\Customer\Block\Address;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Block\Address\Edit;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\NegotiableQuote\Plugin\Customer\Block\Address\EditPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EditPluginTest extends TestCase
{
    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilder;

    /**
     * @var EditPlugin
     */
    private $editPlugin;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->urlBuilder = $this->getMockForAbstractClass(UrlInterface::class);
        $objectManager = new ObjectManager($this);
        $this->editPlugin = $objectManager->getObject(
            EditPlugin::class,
            [
                'urlBuilder' => $this->urlBuilder
            ]
        );
    }

    /**
     * Test afterGetSaveUrl
     */
    public function testAfterGetSaveUrl()
    {
        $address = $this->getMockForAbstractClass(AddressInterface::class);
        $address->expects($this->any())->method('getId')->willReturn(1);
        /**
         * @var Edit|MockObject $subject
         */
        $subject = $this->createMock(Edit::class);
        $subject->expects($this->any())->method('getAddress')->willReturn($address);
        $url = 'url';
        $this->urlBuilder->expects($this->any())->method('getUrl')->willReturn($url);

        $this->assertEquals($url, $this->editPlugin->afterGetSaveUrl($subject, ''));
    }
}
