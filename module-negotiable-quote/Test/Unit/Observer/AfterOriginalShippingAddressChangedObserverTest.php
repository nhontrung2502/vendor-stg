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
use Magento\NegotiableQuote\Observer\AfterOriginalShippingAddressChangedObserver;
use Magento\Tax\Helper\Data;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AfterOriginalShippingAddressChangedObserverTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManagerHelper;

    /**
     * @var AfterOriginalShippingAddressChangedObserver
     */
    private $afterOriginalShippingAddressChangedObserver;

    /**
     * @var NegotiableQuoteTaxRecalculate|MockObject
     */
    private $taxRecalculateMock;

    /**
     * @var Data|MockObject
     */
    private $taxConfigMock;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->taxRecalculateMock = $this->getMockBuilder(NegotiableQuoteTaxRecalculate::class)
            ->disableOriginalConstructor()
            ->setMethods(['recalculateTax'])
            ->getMock();

        $this->taxConfigMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTaxBasedOn'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManager($this);
        $this->afterOriginalShippingAddressChangedObserver = $this->objectManagerHelper->getObject(
            AfterOriginalShippingAddressChangedObserver::class,
            [
                'taxRecalculate' => $this->taxRecalculateMock,
                'taxConfig' => $this->taxConfigMock,
            ]
        );
    }

    /**
     * A test for execute() method.
     *
     * @return void
     */
    public function testExecute()
    {
        /** @var Observer|MockObject $observer */
        $observer = $this->getMockBuilder(Observer::class)
            ->setMethods(['getDataObject'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->taxConfigMock->expects($this->once())->method('getTaxBasedOn')->willReturn('origin');
        $this->taxRecalculateMock->expects($this->once())->method('recalculateTax');

        $this->afterOriginalShippingAddressChangedObserver->execute($observer);
    }
}
