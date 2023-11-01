<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Notification;

/**
 * Interface for generic notification sender.
 *
 * @api
 */
interface SenderInterface
{
    /**
     * Send notification.
     *
     * @param ContentSourceInterface $contentSource
     * @return void
     */
    public function send(ContentSourceInterface $contentSource) : void;
}
