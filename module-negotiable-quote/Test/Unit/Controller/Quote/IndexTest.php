<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Quote;

use Magento\Customer\Model\Url;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Controller\Quote\Index;
use Magento\NegotiableQuote\Helper\Quote;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IndexTest extends TestCase
{
    /**
     * @var PageFactory|MockObject
     */
    protected $resultPageFactory;

    /**
     * @var Quote|MockObject
     */
    protected $quoteHelper;

    /**
     * @var Url|MockObject
     */
    protected $customerUrl;

    /**
     * @var JsonFactory|MockObject
     */
    protected $resultJsonFactory;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    protected $quoteRepository;

    /**
     * @var RestrictionInterface|MockObject
     */
    protected $customerRestriction;

    /**
     * @var Validator|MockObject
     */
    protected $formKeyValidator;

    /**
     * @var NegotiableQuoteManagementInterface|MockObject
     */
    protected $negotiableQuoteManagement;

    /**
     * @var Index|MockObject
     */
    protected $indexMock;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->resultPageFactory =
            $this->createPartialMock(PageFactory::class, ['create']);
        $resultPage =
            $this->getMockBuilder(Page::class)
                ->addMethods(['getTitle', 'set'])
                ->onlyMethods(['getConfig'])
                ->disableOriginalConstructor()
                ->getMock();
        $resultPage->expects($this->any())->method('getConfig')->willReturnSelf();
        $resultPage->expects($this->any())->method('getTitle')->willReturnSelf();
        $resultPage->expects($this->any())->method('set')->willReturnSelf();
        $this->resultPageFactory->expects($this->any())->method('create')->willReturn($resultPage);
        $this->resultFactory = $this->createPartialMock(ResultFactory::class, ['create']);
        $this->resultFactory
            ->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_PAGE)
            ->willReturn($resultPage);
        $this->quoteHelper = $this->createMock(Quote::class);
        $this->customerUrl = $this->createMock(Url::class);
        $this->resultJsonFactory =
            $this->createMock(JsonFactory::class);
        $this->quoteRepository = $this->getMockForAbstractClass(CartRepositoryInterface::class);
        $this->customerRestriction =
            $this->getMockForAbstractClass(RestrictionInterface::class);
        $this->formKeyValidator =
            $this->createMock(Validator::class);
        $this->negotiableQuoteManagement =
            $this->getMockForAbstractClass(NegotiableQuoteManagementInterface::class);
        $objectManager = new ObjectManager($this);
        $this->indexMock = $objectManager->getObject(
            Index::class,
            [
                'resultPageFactory' => $this->resultPageFactory,
                'quoteHelper' => $this->quoteHelper,
                'customerUrl' => $this->customerUrl,
                'resultJsonFactory' => $this->resultJsonFactory,
                'quoteRepository' => $this->quoteRepository,
                'customerRestriction' => $this->customerRestriction,
                'formKeyValidator' => $this->formKeyValidator,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagement,
                'resultFactory' => $this->resultFactory
            ]
        );
    }

    /**
     * Test execute
     */
    public function testExecute()
    {
        $this->assertInstanceOf(Page::class, $this->indexMock->execute());
    }
}
