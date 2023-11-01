<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PurchaseOrder\Ui\Component\Listing\Column;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterfaceFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * UiComponent class for purchase orders listing 'Total' column.
 */
class Price extends Column
{
    /**
     * @var PriceCurrencyInterface
     */
    private $priceFormatter;

    /**
     * @var PurchaseOrderInterfaceFactory
     */
    private $purchaseOrderFactory;

    /**
     * @var Json
     */
    private $serializerJson;

    /**
     * Price constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param PriceCurrencyInterface $priceFormatter
     * @param PurchaseOrderInterfaceFactory $purchaseOrderFactory
     * @param Json $serializerJson
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        PriceCurrencyInterface $priceFormatter,
        PurchaseOrderInterfaceFactory $purchaseOrderFactory,
        Json $serializerJson,
        array $components = [],
        array $data = []
    ) {
        $this->priceFormatter = $priceFormatter;
        $this->purchaseOrderFactory = $purchaseOrderFactory;
        $this->serializerJson = $serializerJson;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare the data source for the column.
     *
     * Formats the total price of each purchase order using the specified currency code.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $purchaseOrder = $this->purchaseOrderFactory->create(['data' => $item]);
                $associatedQuoteData = $purchaseOrder->getData(PurchaseOrderInterface::SNAPSHOT);
                $associatedQuoteData = $this->serializerJson->unserialize($associatedQuoteData);
                $currencyCode = $associatedQuoteData['quote']['quote_currency_code'] ?? null;

                $item[$this->getData('name')] = $this->priceFormatter->format(
                    $item[$this->getData('name')],
                    false,
                    PriceCurrencyInterface::DEFAULT_PRECISION,
                    null,
                    $currencyCode
                );
            }
        }

        return $dataSource;
    }
}
