<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Ui\Component\Listing\Column;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\Ui\Component\Listing\Columns\Column;
use PHPUnit\Framework\TestCase;

/**
 * Abstract class ColumnTest
 */
abstract class ColumnTest extends TestCase
{
    /**#@+*/
    const COLUMN_NAME = 'name';
    /**#@-*/

    /**
     * @var string
     */
    protected $className = '';

    /**
     * @var Column
     */
    protected $column;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $arguments = $objectManagerHelper->getConstructArguments($this->className);
        $arguments = $this->setUpPrepareArguments($arguments);
        $this->column = $objectManagerHelper->getObject($this->className, $arguments);
    }

    /**
     * Prepare set up arguments
     *
     * @param array $arguments
     * @return array
     */
    protected function setUpPrepareArguments(array $arguments)
    {
        $context = $arguments['context'];
        $processorMock =
            $this->createMock(Processor::class);
        $context->expects($this->never())->method('getProcessor')->willReturn($processorMock);
        $arguments['data']['name'] = self::COLUMN_NAME;
        return $arguments;
    }

    /**
     * Data provider data source
     * @return array
     */
    public function prepareDataSourceProvider()
    {
        return [
            [
                [
                    'data' => [
                        'items' => [
                            // item 1
                            [
                                'entity_id' => 1,
                                self::COLUMN_NAME => []
                            ],
                            // item 2
                            [
                                'entity_id' => 2,
                                self::COLUMN_NAME => []
                            ],
                        ]
                    ]
                ]
            ]
        ];
    }
}
