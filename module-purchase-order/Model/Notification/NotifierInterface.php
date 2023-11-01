<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Notification;

use Magento\Framework\Exception\LocalizedException;

/**
 * Notification publisher interface.
 *
 * @api
 */
interface NotifierInterface
{
    /**
     * Publish notification on action.
     *
     * @param int $subjectEntityId
     * @param string $actionNotificationClass
     * @throws LocalizedException
     */
    public function notifyOnAction(
        int $subjectEntityId,
        string $actionNotificationClass
    ) : void;
}
