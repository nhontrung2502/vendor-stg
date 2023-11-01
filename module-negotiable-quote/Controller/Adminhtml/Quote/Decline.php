<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Controller\Adminhtml\Quote;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\NegotiableQuote\Controller\Adminhtml\Quote;

/**
 * If declined, the quote obtains the Declined status. All custom pricing is removed from the quote.
 */
class Decline extends Quote implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_NegotiableQuote::decline';

    /**
     * The merchant declines the quote. All custom pricing is removed from the quote.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $quote = $this->getQuote();
        if ($quote) {
            try {
                $declineComment = $this->getRequest()->getParam('quote_message');
                $this->negotiableQuoteManagement->decline($quote->getId(), $declineComment);
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->messageManager->addError(__('Something went wrong during quote declining'));
            }
        }

        return $this->getRedirect($quote);
    }
}
