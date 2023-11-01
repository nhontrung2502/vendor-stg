<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface as UiComponentContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use \Magento\Ui\Component\Listing\Columns\Column;

/**
 * UiComponent class for purchase orders listing 'Actions' column.
 */
class Actions extends Column
{
    /**#@+
     * Constants
     */
    private const URL_PATH_VIEW = 'purchaseorder/purchaseorder/view';
    /**#@-*/

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * Actions constructor.
     *
     * @param UiComponentContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param array $components
     * @param array $data
     */
    public function __construct(
        UiComponentContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare the data source for the column.
     *
     * Builds the list of available actions for the actions dropdown.
     *
     * @param array $dataSource
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepareDataSource(array $dataSource) :array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['entity_id'])) {
                    $purchaseOrder = $this->purchaseOrderRepository->getById($item['entity_id']);
                    $item['actions'] = $this->getAvailableActions($purchaseOrder);
                }
            }
        }

        return $dataSource;
    }

    /**
     * Build the data array for the available actions for the specified purchase order.
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @return array
     */
    private function getAvailableActions(PurchaseOrderInterface $purchaseOrder)
    {
        $actions = [];
        $purchaseOrderId = $purchaseOrder->getEntityId();

        $actions['view'] = [
            'href' => $this->urlBuilder->getUrl(
                self::URL_PATH_VIEW,
                [
                    'request_id' => $purchaseOrderId
                ]
            ),
            'label' => __('View')
        ];

        return $actions;
    }
}
