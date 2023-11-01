<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Plugin\Checkout\Block;

use Magento\Framework\App\RequestInterface;

/**
 * Plugin for removing discount code on approved PO checkout
 */
class LayoutProcessorPlugin
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * LayoutProcessor constructor.
     *
     * @param RequestInterface $request
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Remove discount component
     *
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterProcess(
        \Magento\Checkout\Block\Checkout\LayoutProcessor $subject,
        $jsLayout
    ) {
        $purchaseOrderId = $this->request->getParam('purchaseOrderId');
        if ($purchaseOrderId) {
            unset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                ['payment']['children']['afterMethods']['children']['discount']);
        }
        return $jsLayout;
    }
}
