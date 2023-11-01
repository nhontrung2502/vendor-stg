<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PurchaseOrder\Logger;

use Magento\Framework\Filesystem\DriverInterface;
use Monolog\Formatter\LineFormatter;

/**
 * Purchase order logger handler class
 */
class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @param DriverInterface $filesystem
     * @param string $filePath
     * @param string $fileName
     * @throws \Exception
     */
    public function __construct(
        DriverInterface $filesystem,
        $filePath = null,
        $fileName = null
    ) {
        $this->fileName = '/var/log/orderapprovalscronorder.log';
        parent::__construct($filesystem, $filePath, $fileName);
    }
}
