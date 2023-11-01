<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Plugin\Company\Model;

use Magento\Company\Api\Data\CompanyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\Plugin\Company\Model\Company;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CompanyTest extends TestCase
{
    /**
     * @var Company|MockObject
     */
    private $companyPlugin;

    /**
     * @var \Magento\NegotiableQuote\Helper\Company|MockObject
     */
    private $companyHelper;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->companyHelper = $this->createMock(\Magento\NegotiableQuote\Helper\Company::class);

        $objectManager = new ObjectManager($this);
        $this->companyPlugin = $objectManager->getObject(
            Company::class,
            [
                'companyHelper' => $this->companyHelper,
            ]
        );
    }

    /**
     * Test for method afterLoad
     */
    public function testAfterLoad()
    {
        $subject = $this->getMockForAbstractClass(CompanyInterface::class, [], '', false);
        $company = $this->getMockForAbstractClass(CompanyInterface::class, [], '', false);
        $this->companyHelper->expects($this->once())->method('loadQuoteConfig')->willReturn($company);

        $this->assertEquals($company, $this->companyPlugin->afterLoad($subject, $company));
    }
}
