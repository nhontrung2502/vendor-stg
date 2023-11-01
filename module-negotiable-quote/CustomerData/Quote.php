<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\NegotiableQuote\CustomerData;

/**
 * Class Quote customer data
 */
class Quote implements \Magento\Customer\CustomerData\SectionSourceInterface
{
    /**
     * @var \Magento\NegotiableQuote\Helper\Quote
     */
    private $quoteHelper;

    /**
     * @param \Magento\NegotiableQuote\Helper\Quote $quoteHelper
     */
    public function __construct(
        \Magento\NegotiableQuote\Helper\Quote $quoteHelper
    ) {
        $this->quoteHelper = $quoteHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getSectionData()
    {
        return [
            'is_enabled' => $this->quoteHelper->isEnabled()
        ];
    }
}
