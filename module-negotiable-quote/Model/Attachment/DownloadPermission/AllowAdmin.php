<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Model\Attachment\DownloadPermission;

/**
 * Class AllowAdmin
 */
class AllowAdmin implements AllowInterface
{
    /**
     * {@inheritdoc}
     */
    public function isAllowed($attachmentId)
    {
        return true;
    }
}
