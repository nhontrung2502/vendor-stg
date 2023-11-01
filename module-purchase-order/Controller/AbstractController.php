<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PurchaseOrder\Controller;

use Magento\Company\Model\CompanyContext;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context as ActionContext;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\PurchaseOrder\Model\Customer\Authorization;

/**
 * Abstract controller class for purchase order actions.
 */
abstract class AbstractController extends Action
{
    /**
     * @var Authorization
     */
    protected $purchaseOrderActionAuth;

    /**
     * @var CompanyContext
     */
    protected $companyContext;

    /**
     * AbstractController constructor.
     * @param ActionContext $actionContext
     * @param CompanyContext $companyContext
     * @param Authorization $purchaseOrderActionAuth
     */
    public function __construct(
        ActionContext $actionContext,
        CompanyContext $companyContext,
        Authorization $purchaseOrderActionAuth
    ) {
        parent::__construct($actionContext);
        $this->purchaseOrderActionAuth = $purchaseOrderActionAuth;
        $this->companyContext = $companyContext;
    }

    /**
     * @inheritDoc
     */
    public function dispatch(RequestInterface $request)
    {
        if (!$this->companyContext->getCustomerId()) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
            return $this->_redirect('customer/account/login');
        } elseif (!$this->isAllowed()) {
            if ($this->companyContext->isCurrentUserCompanyUser()) {
                return $this->_redirect('company/accessdenied');
            }

            return $this->_redirect('noroute');
        }

        return parent::dispatch($request);
    }

    /**
     * Get a page result object.
     *
     * @return ResultInterface
     */
    protected function getResultPage()
    {
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
    }

    /**
     * Get a json result object.
     *
     * @return ResultInterface
     */
    protected function getResultJson()
    {
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
    }

    /**
     * Check if the action is allowed.
     *
     * @return bool
     */
    protected function isAllowed()
    {
        return $this->companyContext->isResourceAllowed('Magento_PurchaseOrder::all');
    }
}
