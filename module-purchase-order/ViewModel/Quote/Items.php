<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\ViewModel\Quote;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Msrp\Helper\Data as MsrpDataHelper;
use Magento\PurchaseOrder\Model\PriceFormatter;

/**
 * Block View Model for Purchase Order Quote Items
 */
class Items implements ArgumentInterface
{
    /**
     * @var PriceFormatter
     */
    private $priceFormatter;

    /**
     * @var MsrpDataHelper
     */
    private $msrpDataHelper;

    /**
     * @param PriceFormatter $priceFormatter
     * @param MsrpDataHelper $msrpDataHelper
     */
    public function __construct(
        PriceFormatter $priceFormatter,
        MsrpDataHelper $msrpDataHelper
    ) {
        $this->priceFormatter = $priceFormatter;
        $this->msrpDataHelper = $msrpDataHelper;
    }

    /**
     * Get Price Formatter
     *
     * @return PriceFormatter
     */
    public function getPriceFormatter()
    {
        return $this->priceFormatter;
    }

    /**
     * Get MSRP Data Helper
     *
     * @return MsrpDataHelper
     */
    public function getMsrpDataHelper()
    {
        return $this->msrpDataHelper;
    }
}
