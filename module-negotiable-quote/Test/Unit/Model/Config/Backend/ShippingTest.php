<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\Config\Backend\Shipping;
use Magento\NegotiableQuote\Model\NegotiableQuoteTaxRecalculate;
use Magento\Tax\Helper\Data;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShippingTest extends TestCase
{
    /**
     * @var Shipping
     */
    private $model;

    /**
     * @var NegotiableQuoteTaxRecalculate|MockObject
     */
    private $taxRecalculate;

    /**
     * @var Data|MockObject
     */
    private $taxHelper;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->taxRecalculate = $this->createMock(NegotiableQuoteTaxRecalculate::class);
        $this->taxHelper = $this->createMock(Data::class);

        $registry = $this->createMock(Registry::class);
        $config = $this->getMockForAbstractClass(
            ScopeConfigInterface::class,
            [],
            '',
            false
        );
        $cacheTypeList = $this->getMockForAbstractClass(
            TypeListInterface::class,
            [],
            '',
            false
        );

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            Shipping::class,
            [
                'registry' => $registry,
                'config' => $config,
                'cacheTypeList' => $cacheTypeList,
                'taxRecalculate' => $this->taxRecalculate,
                'taxHelper' => $this->taxHelper,
            ]
        );
    }

    /**
     * Test for method afterSave with origin
     */
    public function testAfterSaveWithOrigin()
    {
        $this->taxHelper->expects($this->any())->method('getTaxBasedOn')->willReturn('origin');
        $this->taxRecalculate->expects($this->any())->method('setNeedRecalculate')->willReturnSelf();

        $this->assertInstanceOf(get_class($this->model), $this->model->afterSave());
    }

    /**
     * Test for method afterSave without origin
     */
    public function testAfterSaveWithoutOrigin()
    {
        $this->taxHelper->expects($this->any())->method('getTaxBasedOn')->willReturn(null);

        $this->assertInstanceOf(get_class($this->model), $this->model->afterSave());
    }
}
