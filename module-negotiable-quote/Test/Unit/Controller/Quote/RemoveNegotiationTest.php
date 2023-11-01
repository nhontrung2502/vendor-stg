<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Quote;

use Exception;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Controller\Quote\RemoveNegotiation;
use Magento\NegotiableQuote\Model\SettingsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RemoveNegotiationTest extends TestCase
{
    /**
     * @var NegotiableQuoteManagementInterface|MockObject
     */
    private $negotiableQuoteManagement;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var RemoveNegotiation|MockObject
     */
    private $removeNegotiation;

    /**
     * @var Json|MockObject
     */
    private $json;

    /**
     * @var SettingsProvider|MockObject
     */
    private $settingsProvider;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->negotiableQuoteManagement = $this->createMock(
            NegotiableQuoteManagementInterface::class
        );
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->json = $this->createPartialMock(Json::class, ['setData']);
        $this->settingsProvider = $this->createPartialMock(
            SettingsProvider::class,
            ['retrieveJsonError', 'retrieveJsonSuccess']
        );
        $this->settingsProvider->expects($this->once())->method('retrieveJsonSuccess')->willReturn($this->json);
        $objectManager = new ObjectManager($this);
        $resource = $this->createMock(Http::class);
        $resource->method('getParam')
            ->with('quote_id')
            ->willReturn(1);
        $this->removeNegotiation = $objectManager->getObject(
            RemoveNegotiation::class,
            [
                'negotiableQuoteManagement' => $this->negotiableQuoteManagement,
                'logger' => $this->logger,
                '_request' => $resource,
                'settingsProvider' => $this->settingsProvider
            ]
        );
    }

    /**
     * Test execute.
     *
     * @return void
     */
    public function testExecute(): void
    {
        $this->negotiableQuoteManagement->expects($this->once())->method('removeNegotiation');
        $this->assertInstanceOf(Json::class, $this->removeNegotiation->execute());
    }

    /**
     * Test execute.
     *
     * @return void
     */
    public function testExecuteWithException(): void
    {
        $this->negotiableQuoteManagement->expects($this->any())
            ->method('removeNegotiation')
            ->willThrowException(new Exception());
        $this->settingsProvider->expects($this->once())->method('retrieveJsonError')->willReturn($this->json);

        $this->assertInstanceOf(Json::class, $this->removeNegotiation->execute());
    }
}
