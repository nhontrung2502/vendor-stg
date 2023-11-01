<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Plugin\Company\Controller\Adminhtml\Index;

use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Controller\Adminhtml\Index\Save;
use Magento\Company\Model\Company\DataProvider;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\CompanyQuoteConfigInterface;
use Magento\NegotiableQuote\Helper\Company;
use Magento\NegotiableQuote\Plugin\Company\Controller\Adminhtml\Index\SavePlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SavePluginTest extends TestCase
{
    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var Company|MockObject
     */
    protected $companyHelper;

    /**
     * @var SavePlugin
     */
    private $savePlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->request = $this->getMockForAbstractClass(RequestInterface::class);
        $this->companyHelper = $this->createMock(Company::class);

        $objectManager = new ObjectManager($this);
        $this->savePlugin = $objectManager->getObject(
            SavePlugin::class,
            [
                'request' => $this->request,
                'companyHelper' => $this->companyHelper,
            ]
        );
    }

    /**
     * Test for afterSetCompanyRequestData method.
     *
     * @return void
     */
    public function testAfterSetCompanyRequestData()
    {
        $params = [
            DataProvider::DATA_SCOPE_SETTINGS => [
                'is_quote_enabled' => true,
            ]
        ];
        $subject = $this->createMock(Save::class);
        $company = $this->getMockForAbstractClass(CompanyInterface::class);
        $this->request->expects($this->once())->method('getParams')->willReturn($params);
        $quoteConfig = $this->getMockForAbstractClass(CompanyQuoteConfigInterface::class);
        $this->companyHelper->expects($this->once())
            ->method('getQuoteConfig')->with($company)->willReturn($quoteConfig);
        $quoteConfig->expects($this->once())->method('setIsQuoteEnabled')
            ->with($params[DataProvider::DATA_SCOPE_SETTINGS]['is_quote_enabled'])->willReturnSelf();
        $this->assertEquals($company, $this->savePlugin->afterSetCompanyRequestData($subject, $company));
    }
}
