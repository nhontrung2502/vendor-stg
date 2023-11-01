<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\NegotiableQuote\Controller\Adminhtml\Index;

use Magento\Framework\App\Action\HttpPostActionInterface;

class Quotes extends \Magento\Customer\Controller\Adminhtml\Index implements HttpPostActionInterface
{
    /**
     * Customer negotiable quotes grid
     *
     * @return \Magento\Framework\View\Result\Layout
     */
    public function execute()
    {
        $this->initCurrentCustomer();
        $resultLayout = $this->resultLayoutFactory->create();
        return $resultLayout;
    }
}
