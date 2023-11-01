<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrder\Block\PurchaseOrder;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Framework\View\Element\Template;
use Magento\PurchaseOrder\Model\Config as PurchaseOrderConfig;
use Magento\Company\Api\AuthorizationInterface;
use Magento\Company\Model\Company\Structure as CompanyStructure;
use Magento\Framework\Exception\LocalizedException;

/**
 * Block class for the purchase order grid.
 *
 * @api
 */
class Grid extends Template
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
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var CompanyStructure
     */
    private $companyStructure;

    /**
     * @param TemplateContext $context
     * @param PurchaseOrderConfig $purchaseOrderConfig
     * @param AuthorizationInterface $authorization
     * @param UserContextInterface $userContext
     * @param CompanyStructure $companyStructure
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        PurchaseOrderConfig $purchaseOrderConfig,
        AuthorizationInterface $authorization,
        UserContextInterface $userContext,
        CompanyStructure $companyStructure,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->purchaseOrderConfig = $purchaseOrderConfig;
        $this->authorization = $authorization;
        $this->userContext = $userContext;
        $this->companyStructure = $companyStructure;
    }

    /**
     * Check user permissions.
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isAllowed()
    {
        if ($this->getData('currentCustomer')) {
            return $this->isAllowedForCurrentCustomer();
        } else {
            return $this->isAllowedForCompany();
        }
    }

    /**
     * Check purchase orders permissions before rendering the template.
     *
     * @return string
     * @throws LocalizedException
     */
    public function _toHtml()
    {
        if ($this->isAllowed()) {
            return parent::_toHtml();
        }

        return '';
    }

    /**
     * Check if current user has permissions to view purchase orders.
     *
     * @return bool
     */
    private function isAllowedForCurrentCustomer()
    {
        return $this->purchaseOrderConfig->isEnabledForCurrentCustomerAndWebsite()
            && $this->authorization->isAllowed('Magento_PurchaseOrder::view_purchase_orders');
    }

    /**
     * Check if current user has permissions to view company purchase orders.
     *
     * @return bool
     * @throws LocalizedException
     */
    private function isAllowedForCompany()
    {
        if ($this->authorization->isAllowed('Magento_PurchaseOrder::view_purchase_orders_for_company')) {
            return true;
        }

        if ($this->authorization->isAllowed('Magento_PurchaseOrder::view_purchase_orders_for_subordinates')) {
            $customerId = $this->userContext->getUserId();
            return !empty($this->companyStructure->getAllowedChildrenIds($customerId));
        }

        return false;
    }
}
