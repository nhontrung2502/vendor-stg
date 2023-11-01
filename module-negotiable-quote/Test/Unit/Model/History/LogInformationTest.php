<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\History;

use Magento\Customer\Block\Address\Renderer\RendererInterface;
use Magento\Customer\Model\Address\Config;
use Magento\Framework\DataObject;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Helper\Quote;
use Magento\NegotiableQuote\Model\History\LogInformation;
use Magento\NegotiableQuote\Model\HistoryManagementInterface;
use Magento\NegotiableQuote\Model\ResourceModel\History\Collection;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\NegotiableQuote\Model\History\LogInformation class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LogInformationTest extends TestCase
{
    /**
     * @var RestrictionInterface|MockObject
     */
    private $restriction;

    /**
     * @var Quote|MockObject
     */
    private $negotiableQuoteHelper;

    /**
     * @var HistoryManagementInterface|MockObject
     */
    private $historyManagement;

    /**
     * @var Config|MockObject
     */
    private $addressConfig;

    /**
     * @var ResolverInterface|MockObject
     */
    private $localeResolver;

    /**
     * @var LogInformation
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->restriction = $this->getMockBuilder(
            RestrictionInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->negotiableQuoteHelper = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->historyManagement = $this->getMockBuilder(
            HistoryManagementInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->addressConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeResolver = $this->getMockBuilder(ResolverInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->model = $objectManagerHelper->getObject(
            LogInformation::class,
            [
                'restriction' => $this->restriction,
                'negotiableQuoteHelper' => $this->negotiableQuoteHelper,
                'historyManagement' => $this->historyManagement,
                'addressConfig' => $this->addressConfig,
                'localeResolver' => $this->localeResolver,
            ]
        );
    }

    /**
     * Test isCanSubmit method.
     *
     * @return void
     */
    public function testIsCanSubmit()
    {
        $canSubmit = true;
        $this->restriction->expects($this->once())->method('canSubmit')->willReturn(true);

        $this->assertEquals($canSubmit, $this->model->isCanSubmit());
    }

    /**
     * Test getQuoteHistory method.
     *
     * @return void
     */
    public function testGetQuoteHistory()
    {
        $quoteId = 1;
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEntityId'])
            ->getMockForAbstractClass();
        $historyCollection = $this->getMockBuilder(
            Collection::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->negotiableQuoteHelper->expects($this->atLeastOnce())->method('resolveCurrentQuote')->willReturn($quote);
        $quote->expects($this->atLeastOnce())->method('getEntityId')->willReturn($quoteId);
        $this->historyManagement->expects($this->once())->method('updateSystemLogsStatus')->with($quoteId);
        $this->historyManagement->expects($this->once())
            ->method('getQuoteHistory')
            ->with($quoteId)
            ->willReturn($historyCollection);

        $this->assertEquals($historyCollection, $this->model->getQuoteHistory());
    }

    /**
     * Test getQuoteUpdates method.
     *
     * @return void
     */
    public function testGetQuoteUpdates()
    {
        $logId = 1;
        $this->historyManagement->expects($this->once())
            ->method('getLogUpdatesList')
            ->with($logId)
            ->willReturn(
                [
                    'negotiated_price' => 12,
                    'negotiated_price_type' => 'fixed',
                ]
            );
        $updates = new DataObject();
        $updates->setData(['negotiated_price_type' => 'fixed']);

        $this->assertEquals($updates, $this->model->getQuoteUpdates($logId));
    }

    /**
     * Test isSetPostcode method.
     *
     * @return void
     */
    public function testIsSetPostcode()
    {
        $flatAddressArray = [
            AddressInterface::KEY_POSTCODE => '222222'
        ];

        $this->assertTrue($this->model->isSetPostcode($flatAddressArray));
    }

    /**
     * Test getLogAddressRenderer method.
     *
     * @return void
     */
    public function testGetLogAddressRenderer()
    {
        $format = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRenderer'])
            ->getMock();
        $renderer = $this->getMockBuilder(RendererInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->addressConfig->expects($this->once())->method('getFormatByCode')->with('html')->willReturn($format);
        $format->expects($this->once())->method('getRenderer')->willReturn($renderer);

        $this->assertEquals($renderer, $this->model->getLogAddressRenderer());
    }

    /**
     * Test formatDate method.
     *
     * @return void
     */
    public function testFormatDate()
    {
        $dateType = \IntlDateFormatter::LONG;
        $date = date('Y-m-d H:i:s');
        $this->localeResolver->expects($this->once())->method('getLocale')->willReturn('US');

        $this->assertEquals(date('F j, Y'), $this->model->formatDate($date, $dateType));
    }
}
