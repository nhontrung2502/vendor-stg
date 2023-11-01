<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Plugin\Company\Model;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyExtensionFactory;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\Company;
use Magento\Company\Api\CompanyRepositoryInterface as CompanyRepositoryModel;
use Magento\PurchaseOrder\Model\Company\Config\Repository as PurchaseOrderCompanyConfigRepository;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * CompanyRepository plugin for saving purchase order company config
 */
class CompanyRepository
{
    /**
     * @var PurchaseOrderCompanyConfigRepository
     */
    private $purchaseOrderCompanyConfigRespository;

    /**
     * @var CompanyExtensionFactory
     */
    private $companyExtensionFactory;

    /**
     * @param PurchaseOrderCompanyConfigRepository $purchaseOrderCompanyConfigRespository
     * @param CompanyExtensionFactory $companyExtensionFactory
     */
    public function __construct(
        PurchaseOrderCompanyConfigRepository $purchaseOrderCompanyConfigRespository,
        CompanyExtensionFactory $companyExtensionFactory
    ) {
        $this->purchaseOrderCompanyConfigRespository = $purchaseOrderCompanyConfigRespository;
        $this->companyExtensionFactory = $companyExtensionFactory;
    }

    /**
     * Save purchase order company config
     *
     * @param CompanyRepositoryModel $subject
     * @param Company $company
     * @return Company
     * @throws CouldNotSaveException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(CompanyRepositoryModel $subject, Company $company)
    {
        $purchaseOrderCompanyConfig = $this->purchaseOrderCompanyConfigRespository->get($company->getId());
        $extensionAttributes = $company->getExtensionAttributes();
        $purchaseOrderCompanyConfig->setCompanyId($company->getId());
        $purchaseOrderCompanyConfig->setIsPurchaseOrderEnabled(
            (bool) $extensionAttributes->getIsPurchaseOrderEnabled()
        );
        $this->purchaseOrderCompanyConfigRespository->save($purchaseOrderCompanyConfig);
        return $company;
    }

    /**
     * After get company
     *
     * @param CompanyRepositoryInterface $subject
     * @param CompanyInterface $company
     * @return CompanyInterface
     */
    public function afterGet(
        CompanyRepositoryInterface $subject,
        CompanyInterface $company
    ) {
        $purchaseOrderCompanyConfig = $this->purchaseOrderCompanyConfigRespository->get($company->getId());
        $extensionAttributes = $company->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->companyExtensionFactory->create();
        }
        $extensionAttributes->setIsPurchaseOrderEnabled((bool) $purchaseOrderCompanyConfig->isPurchaseOrderEnabled());
        $company->setExtensionAttributes($extensionAttributes);
        return $company;
    }
}
