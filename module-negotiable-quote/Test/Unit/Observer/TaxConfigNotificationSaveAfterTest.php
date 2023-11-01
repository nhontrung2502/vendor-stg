<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\NegotiableQuoteTaxRecalculate;
use Magento\NegotiableQuote\Observer\TaxConfigNotificationSaveAfter;
use Magento\Tax\Model\Config;
use Magento\Tax\Model\Config\Notification;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Magento\NegotiableQuote\Observer\TaxConfigNotificationSaveAfter class.
 */
class TaxConfigNotificationSaveAfterTest extends TestCase
{
    /**
     * @var NegotiableQuoteTaxRecalculate|MockObject
     */
    private $taxRecalculate;

    /**
     * @var TaxConfigNotificationSaveAfter
     */
    private $observer;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->taxRecalculate = $this->getMockBuilder(
            NegotiableQuoteTaxRecalculate::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->observer = $objectManager->getObject(
            TaxConfigNotificationSaveAfter::class,
            [
                'taxRecalculate' => $this->taxRecalculate,
            ]
        );
    }

    public function testExecute()
    {
        $observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDataObject'])
            ->getMock();
        $dataObject = $this->getMockBuilder(Notification::class)
            ->disableOriginalConstructor()
            ->setMethods(['isValueChanged', 'getPath'])
            ->getMock();
        $observer->expects($this->exactly(2))->method('getDataObject')->willReturn($dataObject);
        $dataObject->expects($this->once())->method('isValueChanged')->willReturn(true);
        $dataObject->expects($this->once())->method('getPath')
            ->willReturn(Config::CONFIG_XML_PATH_BASED_ON);
        $this->taxRecalculate->expects($this->once())->method('recalculateTax')->with(true);

        $this->observer->execute($observer);
    }

    public function testNotExecuteWhenChangedNotBasedOnConfig()
    {
        $observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDataObject'])
            ->getMock();
        $dataObject = $this->getMockBuilder(Notification::class)
            ->disableOriginalConstructor()
            ->setMethods(['isValueChanged', 'getPath'])
            ->getMock();
        $observer->expects($this->once())->method('getDataObject')->willReturn($dataObject);
        $dataObject->expects($this->never())->method('isValueChanged');
        $dataObject->expects($this->once())->method('getPath')->willReturn('another config path');
        $this->taxRecalculate->expects($this->never())->method('recalculateTax');

        $this->observer->execute($observer);
    }
}
