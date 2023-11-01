<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\NegotiableQuote\Block\Adminhtml\CustomerEdit\Tab\Grid\Column\Renderer;

/**
 * Adminhtml customer details negotiable quotes grid block action item renderer
 */
class Action extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * Render actions
     *
     * @param  \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $viewAction = [
            '@' => [
                'href' => $this->getUrl(
                    'quotes/quote/view',
                    [
                        'quote_id' => $row->getId(),
                        'customer_id' => $this->getRequest()->getParam('id')
                    ]
                ),
            ],
            '#' => __('View'),
        ];

        $attributesObject = new \Magento\Framework\DataObject();
        $attributesObject->setData($viewAction['@']);
        $html = '<a ' . $attributesObject->serialize() . '>' . $viewAction['#'] . '</a>';

        return $html;
    }
}
