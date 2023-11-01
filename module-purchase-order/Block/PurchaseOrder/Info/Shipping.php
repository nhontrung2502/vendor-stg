<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrder\Block\PurchaseOrder\Info;

use Magento\Customer\Block\Address\Renderer\RendererInterface;
use Magento\Customer\Model\Address\Config as AddressConfig;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Block\PurchaseOrder\AbstractPurchaseOrder;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface as QuoteAddressInterface;

/**
 * Block class for the general shipping info section of the purchase order details page.
 *
 * @api
 * @since 100.2.0
 */
class Shipping extends AbstractPurchaseOrder
{
    /**
     * @var ExtensibleDataObjectConverter
     */
    private $extensibleDataObjectConverter;

    /**
     * @var AddressConfig
     */
    private $addressConfig;

    /**
     * @param TemplateContext $context
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param AddressConfig $addressConfig
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        CartRepositoryInterface $quoteRepository,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        AddressConfig $addressConfig,
        array $data = []
    ) {
        parent::__construct($context, $purchaseOrderRepository, $quoteRepository, $data);
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
        $this->addressConfig = $addressConfig;
    }

    /**
     * Get the shipping address html for the purchase order currently being viewed.
     *
     * This is based on its associated quote.
     *
     * @return mixed
     * @since 100.2.0
     */
    public function getShippingAddressHtml()
    {
        $address = $this->getQuote()->getShippingAddress();

        return $this->getRenderedAddress($address);
    }

    /**
     * Get the billing address html for the purchase order currently being viewed.
     *
     * This is based on its associated quote.
     *
     * @return string
     * @since 100.2.0
     */
    public function getBillingAddressHtml()
    {
        $address = $this->getQuote()->getBillingAddress();

        return $this->getRenderedAddress($address);
    }

    /**
     * Get the rendered address.
     *
     * @param QuoteAddressInterface $address
     * @return string
     */
    private function getRenderedAddress(QuoteAddressInterface $address)
    {
        $flatAddressArray = $this->getFlatAddress($address);

        if (!empty($flatAddressArray[QuoteAddressInterface::KEY_POSTCODE])) {
            /** @var RendererInterface $renderer */
            $renderer = $this->addressConfig->getFormatByCode('html')->getRenderer();

            return $renderer->renderArray($flatAddressArray);
        }

        return '';
    }

    /**
     * Get the quote address as a flat array.
     *
     * @param QuoteAddressInterface $address
     * @return array
     */
    private function getFlatAddress(QuoteAddressInterface $address)
    {
        $flatAddressArray = $this->extensibleDataObjectConverter->toFlatArray(
            $address,
            [],
            QuoteAddressInterface::class
        );

        // Update the street information so that it is indexed appropriately
        $street = $address->getStreet();

        if (!empty($street) && is_array($street)) {

            $streetKeys = array_keys($street);

            foreach ($streetKeys as $key) {
                if (is_array($flatAddressArray)) {
                    unset($flatAddressArray[$key]);
                }
            }

            $flatAddressArray['street'] = $street;
        }

        return $flatAddressArray;
    }
}
