<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Notification;

use Magento\Framework\DataObject;

/**
 * Interface for generic notification content source.
 *
 * @api
 */
interface ContentSourceInterface
{
    /**
     * Get template.
     *
     * @return string
     */
    public function getTemplateConfigPath() : string;

    /**
     * Get email template variables.
     *
     * @return DataObject
     */
    public function getTemplateVars() : DataObject;

    /**
     * Get store ID.
     *
     * @return int
     */
    public function getStoreId() : int;
}
