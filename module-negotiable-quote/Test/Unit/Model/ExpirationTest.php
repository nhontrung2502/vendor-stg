<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Model\Expiration;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ExpirationTest extends TestCase
{
    /**
     * @var Expiration
     */
    private $expiration;

    /**
     * @var Quote|MockObject
     */
    private $quote;

    /**
     * @var TimezoneInterface|MockObject
     */
    private $localeDate;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @var ResolverInterface|MockObject
     */
    private $resolver;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->quote = $this->createMock(Quote::class);

        $this->localeDate = $this
            ->getMockForAbstractClass(TimezoneInterface::class);
        $this->localeDate->expects($this->any())->method('formatDateTime')->willReturnArgument(1);

        $this->scopeConfig = $this
            ->getMockForAbstractClass(ScopeConfigInterface::class);

        $this->resolver = $this->getMockForAbstractClass(ResolverInterface::class);

        $objectManager = new ObjectManager($this);
        $this->expiration = $objectManager->getObject(
            Expiration::class,
            [
                'localeDate' => $this->localeDate,
                'scopeConfig' => $this->scopeConfig,
                'resolver' => $this->resolver
            ]
        );
    }

    /**
     * Test getExpirationPeriodTime method.
     *
     * @param string|null $time
     * @param string $status
     * @param string $timezone
     * @param \DateTime $expect
     * @dataProvider expirationPeriodDataDataProvider
     * @return void
     */
    public function testGetExpirationPeriodTime($time, string $status, string $timezone, \DateTime $expect): void
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
        $this->quote
            ->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);

        $quoteNegotiation = $this->getMockForAbstractClass(NegotiableQuoteInterface::class);
        $quoteNegotiation->expects($this->any())
            ->method('getExpirationPeriod')->willReturn($time);
        $quoteNegotiation->expects($this->any())
            ->method('getStatus')->willReturn($status);
        $this->quote
            ->getExtensionAttributes()
            ->expects($this->any())
            ->method('getNegotiableQuote')
            ->willReturn($quoteNegotiation);
        if ($time !== null) {
            $this->localeDate->expects($this->once())
                ->method('getConfigTimezone')
                ->willReturn($timezone);
        }
        if (empty($time)) {
            $this->scopeConfig->method('getValue')
                ->willReturnOnConsecutiveCalls(5, 'day');
            $this->localeDate->expects($this->any())
                ->method('date')->willReturn($expect);
        }

        $this->assertEquals($expect, $this->expiration->getExpirationPeriodTime($this->quote));
    }

    /**
     * Data provider for getExpirationPeriodTime method.
     *
     * @return array
     */
    public function expirationPeriodDataDataProvider(): array
    {
        $time = time();
        $date = new \DateTime();
        $date->setTimestamp($time);
        $timezone = $date->getTimezone()->getName();
        return [
            [$date->format('c'), NegotiableQuoteInterface::STATUS_CREATED, $timezone, $date],
            [null, NegotiableQuoteInterface::STATUS_CREATED, $timezone, $date],
        ];
    }

    /**
     * Test for method getExpirationPeriodTime.
     *
     * @return void
     */
    public function testGetExpirationPeriodTimeEmpty(): void
    {
        $this->quote
            ->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn(null);

        $this->assertNull($this->expiration->getExpirationPeriodTime($this->quote));
    }
}
