<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Model\Attachment\DownloadPermission;

/**
 * Class AllowInterface
 *
 * @api
 */
interface AllowInterface
{
    /**
     * Is download allowed
     *
     * @param int $attachmentId
     * @return bool
     */
    public function isAllowed($attachmentId);
}
