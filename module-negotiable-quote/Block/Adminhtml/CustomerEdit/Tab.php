<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\NegotiableQuote\Block\Adminhtml\CustomerEdit;

use Magento\Backend\Block\Template\Context;
use Magento\Ui\Component\Layout\Tabs\TabWrapper;
use Magento\Framework\AuthorizationInterface;

/**
 * Block class for Customer Quotes Tab
 */
class Tab extends TabWrapper
{
    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * Constructor
     *
     * @param AuthorizationInterface $authorization
     * @param Context                $context
     * @param array                  $data
     */
    public function __construct(
        AuthorizationInterface $authorization,
        Context $context,
        array $data = []
    ) {
        $this->isAjaxLoaded = true;
        $this->authorization = $authorization;
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     */
    public function canShowTab()
    {
        if (!$this->getRequest()->getParam('id')) {
            return false;
        }

        return $this->authorization->isAllowed('Magento_NegotiableQuote::view_quotes');
    }

    /**
     * Return Tab label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Quotes');
    }

    /**
     * Return URL link to Tab content
     *
     * @return string
     */
    public function getTabUrl()
    {
        return $this->getUrl('quotes/index/quotes', ['_current' => true]);
    }
}
