<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Email;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Api\CustomerNameGenerationInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Model\Email\RecipientFactory;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RecipientFactoryTest extends TestCase
{
    /**
     * @var CompanyManagementInterface|MockObject
     */
    private $companyManagement;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var CustomerNameGenerationInterface|MockObject
     */
    private $customerViewHelper;

    /**
     * @var ResolverInterface|MockObject
     */
    private $localeResolver;

    /**
     * @var RecipientFactory
     */
    private $recipientFactory;

    /**
     * Set up.
     * @return void
     */
    protected function setUp(): void
    {
        $this->companyManagement = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerViewHelper = $this->getMockBuilder(CustomerNameGenerationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->localeResolver = $this->getMockBuilder(ResolverInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $objectManager = new ObjectManager($this);
        $this->recipientFactory = $objectManager->getObject(
            RecipientFactory::class,
            [
                'companyManagement' => $this->companyManagement,
                'storeManager' => $this->storeManager,
                'customerViewHelper' => $this->customerViewHelper,
                'localeResolver' => $this->localeResolver,
            ]
        );
    }

    /**
     * Test createForQuote method.
     * @return void
     */
    public function testCreateForQuote()
    {
        $quote = $this->getMockBuilder(
            CartInterface::class
        )->disableOriginalConstructor()
            ->getMock();
        $customer = $this->getMockBuilder(
            CustomerInterface::class
        )->disableOriginalConstructor()
            ->getMock();
        $customer->expects($this->atLeastOnce())->method('getWebsiteId')->willReturn(2);
        $quote->expects($this->atLeastOnce())->method('getCustomer')->willReturn($customer);
        $website = $this->getMockBuilder(
            WebsiteInterface::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['getStoreIds'])
            ->getMockForAbstractClass();
        $website->expects($this->atLeastOnce())->method('getStoreIds')->willReturn([1]);
        $this->storeManager->expects($this->atLeastOnce())->method('getWebsite')->willReturn($website);
        $this->customerViewHelper->expects($this->atLeastOnce())->method('getCustomerName')->willReturn('Name');
        $company = $this->getMockBuilder(
            CompanyInterface::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->companyManagement->expects($this->atLeastOnce())->method('getByCustomerId')->willReturn($company);
        $extensionAttributes = $this->getMockBuilder(
            CartExtensionInterface::class
        )->setMethods(['getNegotiableQuote'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $negotiableQuote = $this->getMockBuilder(
            NegotiableQuoteInterface::class
        )->disableOriginalConstructor()
            ->getMock();
        $negotiableQuote->expects($this->atLeastOnce())->method('getExpirationPeriod')->willReturn('2020-01-01');
        $extensionAttributes->expects($this->atLeastOnce())->method('getNegotiableQuote')->willReturn($negotiableQuote);
        $quote->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $this->localeResolver->expects($this->atLeastOnce())->method('getLocale')->willReturn('en_US');
        $this->recipientFactory->createForQuote($quote);
    }
}
