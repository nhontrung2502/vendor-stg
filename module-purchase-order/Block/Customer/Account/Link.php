<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrder\Block\Customer\Account;

use Magento\Company\Api\AuthorizationInterface;
use Magento\Customer\Block\Account\SortLinkInterface;
use Magento\Framework\App\DefaultPathInterface;
use Magento\Framework\View\Element\Html\Link\Current as CurrentLink;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\PurchaseOrder\Model\Config as PurchaseOrderConfig;

/**
 * Block class for 'My Purchase Orders' link in customer account.
 *
 * @api
 * @since 100.2.0
 */
class Link extends CurrentLink implements SortLinkInterface
{
    /**
     * @var PurchaseOrderConfig
     */
    private $purchaseOrderConfig;

    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @var string
     */
    private $resource;

    /**
     * @param TemplateContext $context
     * @param DefaultPathInterface $defaultPath
     * @param PurchaseOrderConfig $purchaseOrderConfig
     * @param AuthorizationInterface $authorization
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        DefaultPathInterface $defaultPath,
        PurchaseOrderConfig $purchaseOrderConfig,
        AuthorizationInterface $authorization,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath, $data);

        $this->purchaseOrderConfig = $purchaseOrderConfig;
        $this->authorization = $authorization;
        if (isset($data['resource'])) {
            $this->resource = $data['resource'];
        }
    }

    /**
     * Check if purchase orders are enabled before rendering the template.
     *
     * @return string
     * @since 100.2.0
     */
    protected function _toHtml()
    {
        if ($this->purchaseOrderConfig->isEnabledForCurrentCustomerAndWebsite()
            && $this->authorization->isAllowed($this->resource)) {
            return parent::_toHtml();
        }

        return '';
    }

    /**
     * @inheritDoc
     * @since 100.2.0
     */
    public function getSortOrder()
    {
        return $this->getData(self::SORT_ORDER);
    }
}
