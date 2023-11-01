<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model;

use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\CompanyContext;
use Magento\Company\Model\CompanyManagement;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\PurchaseOrder\Model\Company\Config\Repository as ConfigRepository;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface as StoreScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Company\Model\Config as CompanyConfig;

/**
 * Config Model responsible for checking whether purchase order is enabled for respective website and company
 */
class Config
{
    private const SETTING_DISABLED = '0';

    private const SETTING_ENABLED = '1';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CompanyManagement
     */
    private $companyManagement;

    /**
     * @var CompanyContext
     */
    private $companyContext;

    /**
     * @var CompanyConfig
     */
    private $companyConfig;

    /**
     * @var string
     */
    private $xmlPathEnabled = 'btob/website_configuration/purchaseorder_enabled';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param CompanyManagement $companyManagement
     * @param ConfigRepository $configRepository
     * @param StoreManagerInterface $storeManager
     * @param CompanyContext $companyContext
     * @param CompanyConfig $companyConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CompanyManagement $companyManagement,
        ConfigRepository $configRepository,
        StoreManagerInterface $storeManager,
        CompanyContext $companyContext,
        CompanyConfig $companyConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->companyManagement = $companyManagement;
        $this->configRepository = $configRepository;
        $this->storeManager = $storeManager;
        $this->companyContext = $companyContext;
        $this->companyConfig = $companyConfig;
    }

    /**
     * Is purchase order enabled for current customer and website scope of this request?
     *
     * @return bool
     */
    public function isEnabledForCurrentCustomerAndWebsite()
    {
        $customerId = $this->companyContext->getCustomerId();

        if (!$customerId) {
            return false;
        }

        try {
            $currentWebsite = $this->storeManager->getWebsite();
        } catch (LocalizedException $e) {
            return false;
        }

        $isEnabledForCurrentWebsite = $this->isEnabledForWebsite($currentWebsite);

        if (!$isEnabledForCurrentWebsite) {
            return false;
        }

        $company = $this->companyManagement->getByCustomerId($customerId);

        if (!$company) {
            return false;
        }

        return $this->isEnabledForCompany($company);
    }

    /**
     * Is purchase order enabled for a particular customer?
     *
     * @param CustomerInterface $customer
     * @return bool
     */
    public function isEnabledForCustomer(CustomerInterface $customer)
    {
        $company = $this->companyManagement->getByCustomerId($customer->getId());

        if (!$company || !$this->isEnabledForCompany($company)) {
            return false;
        }

        try {
            $website = $this->storeManager->getWebsite($customer->getWebsiteId());
        } catch (LocalizedException $e) {
            return false;
        }

        return $this->isEnabledForWebsite($website);
    }

    /**
     * Is purchase order enabled for a particular website?
     *
     * @param WebsiteInterface $website
     * @return bool
     */
    public function isEnabledForWebsite(WebsiteInterface $website)
    {
        $isPurchaseOrderEnabledOnWebsiteLevel = $this->scopeConfig->getValue(
            $this->xmlPathEnabled,
            StoreScopeInterface::SCOPE_WEBSITE,
            $website
        ) === self::SETTING_ENABLED;

        if ($isPurchaseOrderEnabledOnWebsiteLevel) {
            return true;
        }

        $isPurchaseOrderDisabledOnWebsiteLevel = $this->scopeConfig->getValue(
            $this->xmlPathEnabled,
            StoreScopeInterface::SCOPE_WEBSITE,
            $website
        ) === self::SETTING_DISABLED;

        if ($isPurchaseOrderDisabledOnWebsiteLevel) {
            return false;
        }

        $isPurchaseOrderEnabledByDefault = $this->scopeConfig->getValue(
            $this->xmlPathEnabled,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        ) === self::SETTING_ENABLED;

        return $isPurchaseOrderEnabledByDefault && !$isPurchaseOrderDisabledOnWebsiteLevel;
    }

    /**
     * Is purchase order enabled for a particular company?
     *
     * @param CompanyInterface $company
     * @return bool
     */
    public function isEnabledForCompany(CompanyInterface $company)
    {
        // check if company module itself is disabled in default scope
        if (!$this->isB2BFeaturesCompanyEnabled()) {
            return false;
        }

        $companyConfig = $this->configRepository->get($company->getId());

        return $companyConfig->isPurchaseOrderEnabled();
    }

    /**
     * Is B2B Features Company Config Setting set to enabled in current website context?
     *
     * @return bool
     */
    private function isB2BFeaturesCompanyEnabled()
    {
        try {
            $website = $this->storeManager->getWebsite();

            $isEnabled = $this->companyConfig->isActive(
                StoreScopeInterface::SCOPE_WEBSITE,
                $website->getCode()
            );
        } catch (LocalizedException $e) {
            $isEnabled = $this->companyConfig->isActive(
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );
        }

        return $isEnabled;
    }
}
