<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Setup\Patch\Data;

use Magento\Company\Setup\CompanySetup;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Initialize permissions.
 */
class InitPermissions implements DataPatchInterface
{
    /**
     * @var CompanySetup
     */
    private $companySetup;

    /**
     * InitPermissions constructor.
     *
     * @param CompanySetup $companySetup
     */
    public function __construct(
        \Magento\Company\Setup\CompanySetup $companySetup
    ) {
        $this->companySetup = $companySetup;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        $this->companySetup->applyPermissions();
        return $this;
    }
}
