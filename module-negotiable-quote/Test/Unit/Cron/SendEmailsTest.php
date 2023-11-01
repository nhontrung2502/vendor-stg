<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Cron;

use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterfaceFactory;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Cron\SendEmails;
use Magento\NegotiableQuote\Model\EmailSenderInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Send Emails.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SendEmailsTest extends TestCase
{
    /**
     * @var NegotiableQuoteInterfaceFactory|MockObject
     */
    private $negotiableQuoteFactory;

    /**
     * @var NegotiableQuoteRepositoryInterface|MockObject
     */
    private $negotiableQuoteRepository;

    /**
     * @var EmailSenderInterface|MockObject
     */
    private $emailSender;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder|MockObject
     */
    private $filterBuilder;

    /**
     * @var TimezoneInterface|MockObject
     */
    private $localeDate;

    /**
     * @var NegotiableQuoteManagementInterface|MockObject
     */
    private $negotiableQuoteManagement;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @var SendEmails
     */
    private $cron;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->negotiableQuoteFactory = $this
            ->getMockBuilder(NegotiableQuoteInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->negotiableQuoteRepository = $this
            ->getMockBuilder(NegotiableQuoteRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['save', 'getList'])
            ->getMockForAbstractClass();
        $this->emailSender = $this
            ->getMockBuilder(EmailSenderInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['sendChangeQuoteEmailToMerchant', 'sendChangeQuoteEmailToBuyer'])
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFilters', 'create'])
            ->getMock();
        $this->filterBuilder = $this->getMockBuilder(FilterBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeDate = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->negotiableQuoteManagement = $this
            ->getMockBuilder(NegotiableQuoteManagementInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuote'])
            ->getMockForAbstractClass();
        $this->scopeConfig = $this
            ->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();
        $objectManager = new ObjectManager($this);
        $this->cron = $objectManager->getObject(
            SendEmails::class,
            [
                'negotiableQuoteFactory' => $this->negotiableQuoteFactory,
                'negotiableQuoteRepository' => $this->negotiableQuoteRepository,
                'emailSender' => $this->emailSender,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'filterBuilder' => $this->filterBuilder,
                'localeDate' => $this->localeDate,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagement,
                'scopeConfig' => $this->scopeConfig,
            ]
        );
    }

    /**
     * Test execute().
     *
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecute()
    {
        $quoteId = 1;
        $currentDate = new \DateTime();
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(SendEmails::CONFIG_QUOTE_EMAIL_NOTIFICATIONS_ENABLED, ScopeInterface::SCOPE_STORE)
            ->willReturn(true);
        $this->localeDate->expects($this->any())->method('date')->willReturn($currentDate);
        $this->filterBuilder
            ->expects($this->exactly(6))
            ->method('setField')
            ->withConsecutive(
                ['extension_attribute_negotiable_quote.expiration_period'],
                ['extension_attribute_negotiable_quote.status_email_notification'],
                ['extension_attribute_negotiable_quote.expiration_period'],
                ['extension_attribute_negotiable_quote.status_email_notification'],
                ['extension_attribute_negotiable_quote.expiration_period'],
                ['extension_attribute_negotiable_quote.status_email_notification']
            )
            ->willReturnSelf();
        $this->filterBuilder
            ->expects($this->exactly(6))
            ->method('setValue')
            ->willReturnSelf();
        $this->filterBuilder
            ->expects($this->exactly(6))
            ->method('setConditionType')
            ->withConsecutive(
                ['eq'],
                ['lteq']
            )
            ->willReturnSelf();
        $this->filterBuilder->expects($this->atLeastOnce())->method('create')->willReturnSelf();
        $searchCriteria = $this
            ->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchResults = $this
            ->getMockBuilder(SearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $cart = $this
            ->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $extensibleData = $this
            ->getMockBuilder(ExtensibleDataInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilder
            ->expects($this->exactly(6))
            ->method('addFilters')
            ->willReturnSelf();
        $this->searchCriteriaBuilder
            ->expects($this->exactly(3))
            ->method('create')
            ->willReturn($searchCriteria);
        $this->negotiableQuoteRepository
            ->expects($this->exactly(3))
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($searchResults);
        $searchResults
            ->expects($this->exactly(3))
            ->method('getItems')
            ->willReturn([$extensibleData]);
        $extensibleData->expects($this->exactly(3))->method('getId')->willReturn($quoteId);
        $negotiableQuoteMock = $this
            ->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setQuoteId', 'setEmailNotificationStatus'])
            ->getMockForAbstractClass();
        $negotiableQuoteMock->expects($this->exactly(3))
            ->method('setQuoteId')
            ->with($quoteId)
            ->willReturnSelf();
        $negotiableQuoteMock->expects($this->exactly(3))
            ->method('setEmailNotificationStatus')
            ->withConsecutive(
                [SendEmails::EMAIL_SENT_TWO_DAYS_COUNTER],
                [SendEmails::EMAIL_SENT_ONE_DAY_COUNTER],
                [SendEmails::EMAIL_SENT_ZERO_DAY_COUNTER],

            )
            ->willReturnSelf();
        $this->negotiableQuoteFactory->expects($this->exactly(3))
            ->method('create')
            ->willReturn($negotiableQuoteMock);
        $this->negotiableQuoteRepository->expects($this->exactly(3))
            ->method('save')
            ->with($negotiableQuoteMock)
            ->willReturn(true);
        $this->negotiableQuoteManagement->expects($this->exactly(3))
            ->method('getNegotiableQuote')
            ->with($quoteId)
            ->willReturn($cart);
        $this->emailSender->expects($this->once())
            ->method('sendChangeQuoteEmailToMerchant')
            ->with($cart, SendEmails::EXPIRE_TWO_DAYS_TEMPLATE)
            ->willReturnSelf();
        $this->emailSender->expects($this->exactly(2))
            ->method('sendChangeQuoteEmailToBuyer')
            ->withConsecutive(
                [$cart, SendEmails::EXPIRE_ONE_DAY_TEMPLATE],
                [$cart, SendEmails::EXPIRED_TEMPLATE],
            )
            ->willReturnSelf();


        $this->cron->execute();
    }
}
