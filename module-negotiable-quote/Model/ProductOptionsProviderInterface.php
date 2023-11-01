<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Model;

/**
 * Provides options data for complex products.
 *
 * @api
 */
interface ProductOptionsProviderInterface
{
    /**
     * Get options provider product type.
     *
     * @return string
     */
    public function getProductType();

    /**
     * Get list of product options.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    public function getOptions(\Magento\Catalog\Model\Product $product);
}
