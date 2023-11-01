<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\NegotiableQuote\Block\Adminhtml\CustomerEdit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data;
use Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory;
use Magento\NegotiableQuote\Block\Adminhtml\CustomerEdit\Tab\Grid\Column\Renderer\Action;
use Magento\NegotiableQuote\Model\Status\LabelProviderInterface;

/**
 * Adminhtml customer negotiable  quotes grid block
 * @api
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var LabelProviderInterface
     */
    private $labelProvider;

    /**
     * @param Context $context
     * @param Data $backendHelper
     * @param CollectionFactory $collectionFactory
     * @param LabelProviderInterface $labelProvider
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        CollectionFactory $collectionFactory,
        LabelProviderInterface $labelProvider,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->labelProvider = $labelProvider;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('negotiable_quotes');
        $this->setDefaultSort('updated_at');
        $this->setDefaultDir('desc');
        $this->setUseAjax(true);
    }

    /**
     * Apply various selection filters to prepare the negotiable quotes grid collection.
     *
     * @return $this
     */
    protected function _prepareCollection()
    {

        $customer_id = $this->getRequest()->getParam('id');
        $collection = $this->collectionFactory->getReport('negotiable_quote_grid_data_source')->addFieldToSelect(
            'entity_id'
        )->addFieldToSelect(
            'created_at'
        )->addFieldToSelect(
            'updated_at'
        )->addFieldToSelect(
            'sales_rep'
        )->addFieldToSelect(
            'grand_total'
        )->addFieldToSelect(
            'negotiated_grand_total'
        )->addFieldToSelect(
            'status'
        )->addFieldToFilter(
            'customer_id',
            $customer_id
        );

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * @inheritdoc
     */
    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', ['header' => __('Quote #'), 'width' => '100', 'index' => 'entity_id']);
        $this->addColumn('created_at', ['header' => __('Created Date'), 'index' => 'created_at', 'type' => 'datetime']);
        $this->addColumn('updated_at', ['header' => __('Last Updated'), 'index' => 'updated_at', 'type' => 'datetime']);
        $this->addColumn('sales_rep', ['header' => __('Sales Rep'), 'index' => 'sales_rep']);
        $this->addColumn('grand_total', ['header' => __('Quote Total (Base)'), 'type' => 'currency',
                'currency' => 'order_currency_code', 'index' => 'grand_total']);
        $this->addColumn('negotiated_grand_total', ['header' => __('Quote Total (Negotiated)'), 'type' => 'currency',
                'currency' => 'order_currency_code', 'index' => 'negotiated_grand_total']);
        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'index' => 'status',
                'type' => 'options',
                'options' => $this->labelProvider->getStatusLabels()
            ]
        );
        $this->addColumn(
            'action',
            [
                'header' => __('Action'),
                'filter' => false,
                'sortable' => false,
                'width' => '100px',
                'renderer' => Action::class
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * @inheritdoc
     */
    public function getGridUrl()
    {
        return $this->getUrl('quotes/*/quotes', ['_current' => true]);
    }
}
