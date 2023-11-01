<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Controller;

use Magento\Framework\App\ObjectManager;

/**
 * Base class for quote controllers.
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class Quote extends \Magento\Framework\App\Action\Action
{
    /**
     * Authorization level of a company session.
     */
    const NEGOTIABLE_QUOTE_RESOURCE = 'Magento_NegotiableQuote::all';

    /**
     * @var \Magento\NegotiableQuote\Helper\Quote
     */
    protected $quoteHelper;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\NegotiableQuote\Model\Restriction\RestrictionInterface
     */
    protected $customerRestriction;

    /**
     * @var \Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface
     */
    protected $negotiableQuoteManagement;

    /**
     * @var \Magento\NegotiableQuote\Model\SettingsProvider
     */
    protected $settingsProvider;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * Quote constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\NegotiableQuote\Helper\Quote $quoteHelper
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\NegotiableQuote\Model\Restriction\RestrictionInterface $customerRestriction
     * @param \Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface $negotiableQuoteManagement
     * @param \Magento\NegotiableQuote\Model\SettingsProvider $settingsProvider
     * @param \Magento\Customer\Model\Session|null $customerSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\NegotiableQuote\Helper\Quote $quoteHelper,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\NegotiableQuote\Model\Restriction\RestrictionInterface $customerRestriction,
        \Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface $negotiableQuoteManagement,
        \Magento\NegotiableQuote\Model\SettingsProvider $settingsProvider,
        \Magento\Customer\Model\Session $customerSession = null
    ) {
        parent::__construct($context);
        $this->quoteHelper = $quoteHelper;
        $this->quoteRepository = $quoteRepository;
        $this->customerRestriction = $customerRestriction;
        $this->negotiableQuoteManagement = $negotiableQuoteManagement;
        $this->settingsProvider = $settingsProvider;
        $this->customerSession = $customerSession ?? ObjectManager::getInstance()
            ->get(\Magento\Customer\Model\Session::class);
    }

    /**
     * Check dispatch allow.
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function dispatch(\Magento\Framework\App\RequestInterface $request)
    {
        if (!$this->settingsProvider->isModuleEnabled()) {
            throw new \Magento\Framework\Exception\NotFoundException(__('Page not found.'));
        }

        if (($this->settingsProvider->getCurrentUserType()
                !== \Magento\Authorization\Model\UserContextInterface::USER_TYPE_CUSTOMER)
            && $this->getRequest()->isAjax()
        ) {
            return $this->settingsProvider->retrieveJsonError('', $this->settingsProvider->getCustomerLoginUrl());
        } elseif (!$this->customerSession->authenticate()) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
            return $this->_redirect('customer/account/login');
        } elseif (!$this->isQuoteEnabled() || !$this->isAllowed()) {
            if ($this->settingsProvider->isCurrentUserCompanyUser()) {
                return $this->_redirect('company/accessdenied');
            }

            $this->_redirect('noroute');
        }

        return parent::dispatch($request);
    }

    /**
     * Is quote enabled.
     *
     * @return bool
     */
    protected function isQuoteEnabled()
    {
        return $this->quoteHelper->isEnabled();
    }

    /**
     * Get result page.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    protected function getResultPage()
    {
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
    }

    /**
     * Get result JSON.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    protected function getResultJson()
    {
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
    }

    /**
     * Check current user permission on resource.
     *
     * @return bool
     */
    protected function isAllowed()
    {
        return $this->customerRestriction->isAllowed(static::NEGOTIABLE_QUOTE_RESOURCE);
    }
}
