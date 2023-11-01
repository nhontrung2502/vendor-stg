<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Ui\Component\Listing\Column;

use Magento\NegotiableQuote\Ui\Component\Listing\Column\Actions;

class ActionsTest extends ColumnTest
{
    /**
     * @var string
     */
    protected $className = Actions::class;

    /**
     * @param $dataSource
     * @dataProvider prepareDataSourceProvider
     */
    public function testPrepareDataSource($dataSource)
    {
        $dataSourceResult = $this->column->prepareDataSource($dataSource);

        foreach ($dataSourceResult['data']['items'] as $item) {
            $view = $item[self::COLUMN_NAME]['view'];
            $this->assertArrayHasKey('href', $view);
            $this->assertArrayHasKey('label', $view);
            $this->assertArrayHasKey('hidden', $view);
        }
    }
}
