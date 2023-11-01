<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Controller\Quote;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\NegotiableQuote\Controller\Quote;
use Magento\Framework\Exception\NotFoundException;

/**
 * Finalize negotiable order. Action processes success page of a checkout.
 *
 * @TODO Finalizing negotiable order shouldn't be done by tapping in to read-only success page.
 */
class Order extends Quote implements HttpGetActionInterface
{
    /**
     * Order quote
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws NotFoundException
     */
    public function execute()
    {
        $quoteId = (int)$this->getRequest()->getParam('quote_id');

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('checkout/onepage/success', ['negotiableQuoteId' => $quoteId]);

        try {
            $quote = $this->quoteRepository->get($quoteId);
            if (((int) $quote->getCustomerId()) === ((int) $this->settingsProvider->getCurrentUserId())) {
                $this->negotiableQuoteManagement->order($quoteId);
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError(
                __('We can\'t order the quote right now because of an error: %1.', $e->getMessage())
            );
        } catch (\Exception $e) {
            $this->messageManager->addError(__('We can\'t order the quote right now.'));
        }

        return $resultRedirect;
    }
}
