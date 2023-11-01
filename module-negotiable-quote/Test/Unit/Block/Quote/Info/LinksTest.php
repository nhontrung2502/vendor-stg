<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Block\Quote\Info;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Company\Api\AuthorizationInterface;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\NegotiableQuote\Block\Quote\Info\Links;
use Magento\NegotiableQuote\Helper\Quote;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LinksTest extends TestCase
{
    /**
     * @var Links
     */
    private $link;

    /**
     * @var Quote|MockObject
     */
    private $negotiableQuoteHelper;

    /**
     * @var Context|MockObject
     */
    private $urlBuilder;

    /**
     * @var CompanyManagementInterface|MockObject
     */
    private $companyManagement;

    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var AuthorizationInterface|MockObject
     */
    private $authorization;

    /**
     * @var int
     */
    private $quoteId;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->quoteId = 1;
        $this->urlBuilder = $this->getMockForAbstractClass(UrlInterface::class);

        $quote = $this->getMockForAbstractClass(CartInterface::class);
        $quote->expects($this->any())->method('getId')->willReturn($this->quoteId);

        $this->negotiableQuoteHelper = $this->createMock(Quote::class);
        $this->negotiableQuoteHelper->expects($this->any())
            ->method('resolveCurrentQuote')
            ->willReturn($quote);
        $this->userContext = $this->getMockForAbstractClass(UserContextInterface::class);
        $this->authorization = $this->getMockForAbstractClass(AuthorizationInterface::class);
        $this->companyManagement  = $this
            ->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getByCustomerId'])
            ->getMockForAbstractClass();
        $objectManager = new ObjectManager($this);
        $this->link = $objectManager->getObject(
            Links::class,
            [
                'negotiableQuoteHelper' => $this->negotiableQuoteHelper,
                'authorization' => $this->authorization,
                '_urlBuilder' => $this->urlBuilder,
                'companyManagement' => $this->companyManagement,
                'userContext' => $this->userContext
            ]
        );

        $layout = $this->getMockForAbstractClass(LayoutInterface::class);
        $this->link->setLayout($layout);
    }

    /**
     * Test getDeleteUrl.
     *
     * @return void
     */
    public function testGetDeleteUrl()
    {
        $path = 'negotiable_quote/quote/delete';
        $url = 'http://example.com/';

        $this->urlBuilder->expects($this->any())
            ->method('getUrl')->willReturn(
                $url . $path . '/quote_id/' . $this->quoteId . '/'
            );

        $this->assertEquals($url . $path . '/quote_id/1/', $this->link->getDeleteUrl());
    }

    /**
     * Test getPrintUrl.
     *
     * @return void
     */
    public function testGetPrintUrl()
    {
        $path = 'negotiable_quote/quote/print';
        $url = 'http://example.com/';

        $this->urlBuilder->expects($this->any())
            ->method('getUrl')->willReturn(
                $url . $path . '/quote_id/' . $this->quoteId . '/'
            );

        $this->assertEquals($url . $path . '/quote_id/1/', $this->link->getPrintUrl());
    }

    /**
     * Test isCheckoutLinkVisible.
     *
     * @param bool $expectedResult
     * @param int $companyStatus
     * @dataProvider isCheckoutLinkVisibleDataProvider
     */
    public function testIsCheckoutLinkVisible($expectedResult, $companyStatus)
    {
        $company  = $this
            ->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStatus'])
            ->getMockForAbstractClass();
        $this->userContext->expects($this->any())->method('getUserId')->willReturn(1);
        $this->companyManagement->expects($this->once())->method('getByCustomerId')->with(1)->willReturn($company);
        $company->expects($this->once())->method('getStatus')->willReturn($companyStatus);
        $this->authorization->expects($this->once())->method('isAllowed')->willReturn($expectedResult);
        $this->assertEquals($expectedResult, $this->link->isCheckoutLinkVisible());
    }

    /**
     * Data provider isCheckoutLinkVisible
     *
     * @return array
     */
    public function isCheckoutLinkVisibleDataProvider()
    {
        return [
            [false, CompanyInterface::STATUS_BLOCKED],
            [true,  CompanyInterface::STATUS_APPROVED]
        ];
    }
}
