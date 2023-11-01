<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Ui\Component\Listing\Column;

use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Model\Status\BackendLabelProvider;
use Magento\NegotiableQuote\Ui\Component\Listing\Column\Status;

class StatusTest extends ColumnTest
{
    /**
     * @var string
     */
    protected $className = Status::class;

    /**
     * Prepare set up arguments
     *
     * @param array $arguments
     * @return array
     */
    protected function setUpPrepareArguments(array $arguments)
    {
        $arguments['labelProvider'] = new BackendLabelProvider();
        return parent::setUpPrepareArguments($arguments);
    }

    /**
     * @param $dataSource
     * @param string $label
     * @dataProvider prepareDataSourceProvider
     */
    public function testPrepareDataSource($dataSource, $label)
    {
        $dataSourceResult = $this->column->prepareDataSource(['data' => $dataSource]);
        foreach ($dataSourceResult['data']['items'] as $item) {
            $view = $item[self::COLUMN_NAME];
            $this->assertEquals($label, $view);
        }
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
                    'items' => [
                        // item 1
                        [
                            'entity_id' => 1,
                            self::COLUMN_NAME => NegotiableQuoteInterface::STATUS_CREATED
                        ]
                    ]
                ],
                'New'
            ],
            [
                [
                    'items' => [
                        // item 1
                        [
                            'entity_id' => 1,
                            self::COLUMN_NAME => NegotiableQuoteInterface::STATUS_CLOSED
                        ]
                    ]
                ],
                'Closed'
            ]
        ];
    }
}
