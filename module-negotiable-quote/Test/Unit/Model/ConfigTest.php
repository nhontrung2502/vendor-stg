<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model;

use Magento\Framework\App\Config\ConfigResource\ConfigInterface as ConfigResource;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ConfigResource|MockObject
     */
    private $configResource;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @var string
     */
    private $string = 'Thisisateststringwithoutspaces';

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->configResource = $this->createMock(
            ConfigResource::class
        );
        $this->scopeConfig = $this->getMockForAbstractClass(
            ScopeConfigInterface::class,
            [],
            '',
            false
        );

        $objectManager = new ObjectManager($this);
        $this->config = $objectManager->getObject(
            Config::class,
            [
                'configResource' => $this->configResource,
                'scopeConfig' => $this->scopeConfig,
            ]
        );
    }

    /**
     * Test for method isActive.
     *
     * @return void
     */
    public function testIsActive()
    {
        $this->scopeConfig->expects($this->once())->method('isSetFlag')->willReturn(true);

        $this->assertTrue($this->config->isActive());
    }

    /**
     * Test for method setIsActive.
     *
     * @return void
     */
    public function testSetIsActive()
    {
        $this->configResource->expects($this->once())->method('saveConfig')->willReturnSelf();

        $this->config->setIsActive(true);
    }

    /**
     * Test for method getAllowedExtensions.
     *
     * @return void
     */
    public function testGetAllowedExtensions()
    {
        $this->scopeConfig->expects($this->once())->method('getValue')->willReturn($this->string);

        $this->assertEquals($this->string, $this->config->getAllowedExtensions());
    }

    /**
     * Test for method getMaxFileSize.
     *
     * @return void
     */
    public function testGetMaxFileSize()
    {
        $this->scopeConfig->expects($this->once())->method('getValue')->willReturn($this->string);

        $this->assertEquals($this->string, $this->config->getMaxFileSize());
    }
}
