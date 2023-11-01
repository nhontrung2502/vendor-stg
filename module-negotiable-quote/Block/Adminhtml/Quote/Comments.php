<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Block\Adminhtml\Quote;

/**
 * Class Comments
 *
 * @api
 * @since 100.0.0
 */
class Comments extends \Magento\NegotiableQuote\Block\Quote\Comments
{
    /**
     * Returns attachment URL
     *
     * @param int $attachmentId
     * @return string
     */
    public function getAttachmentUrl($attachmentId)
    {
        return $this->getUrl('*/*/download', ['attachmentId' => $attachmentId]);
    }
}
