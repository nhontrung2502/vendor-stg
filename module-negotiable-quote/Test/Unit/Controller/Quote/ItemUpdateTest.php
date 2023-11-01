<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Quote;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Controller\Quote\ItemUpdate;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\NegotiableQuote\Model\SettingsProvider;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ItemUpdateTest extends TestCase
{
    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var RestrictionInterface|MockObject
     */
    private $customerRestriction;

    /**
     * @var Validator|MockObject
     */
    private $formKeyValidator;

    /**
     * @var ItemUpdate|MockObject
     */
    private $itemUpdateMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var NegotiableQuoteManagementInterface|MockObject
     */
    private $negotiableQuoteManagement;

    /**
     * @var SettingsProvider|MockObject
     */
    private $settingsProvider;

    /**
     * @var ManagerInterface|MockObject
     */
    protected $messageManager;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->request = $this->getMockForAbstractClass(RequestInterface::class);
        $objectManager = new ObjectManager($this);
        $resultRedirectFactory =
            $this->createPartialMock(RedirectFactory::class, ['create']);
        $this->messageManager = $this->getMockForAbstractClass(ManagerInterface::class);
        $resultRedirect = $this->createMock(Redirect::class);
        $resultRedirectFactory->expects($this->atLeastOnce())->method('create')->willReturn($resultRedirect);
        $this->quoteRepository = $this->getMockForAbstractClass(CartRepositoryInterface::class);
        $this->customerRestriction =
            $this->getMockForAbstractClass(RestrictionInterface::class);
        $this->negotiableQuoteManagement =
            $this->getMockForAbstractClass(NegotiableQuoteManagementInterface::class);
        $this->formKeyValidator =
            $this->createMock(Validator::class);
        $this->settingsProvider =
            $this->createPartialMock(SettingsProvider::class, ['getCurrentUserId']);
        $this->itemUpdateMock = $objectManager->getObject(
            ItemUpdate::class,
            [
                'quoteRepository' => $this->quoteRepository,
                'customerRestriction' => $this->customerRestriction,
                'formKeyValidator' => $this->formKeyValidator,
                'resultRedirectFactory' => $resultRedirectFactory,
                '_request' => $this->request,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagement,
                'settingsProvider' => $this->settingsProvider,
                'messageManager' => $this->messageManager
            ]
        );
    }

    /**
     * Test for method execute.
     *
     * @return void
     */
    public function testExecute(): void
    {
        $this->messageManager->expects($this->never())->method('addSuccessMessage');
        $this->assertInstanceOf(Redirect::class, $this->itemUpdateMock->execute());
    }

    /**
     * Test for method execute with right validation.
     *
     * @return void
     */
    public function testExecuteWithRightValidation(): void
    {
        $cartData = [
            10 => [
                'qty' => 5,
                'before_suggest_qty' => 5
            ]
        ];
        $quote = $this->getMockBuilder(Quote::class)
            ->addMethods(['getCustomerId'])
            ->disableOriginalConstructor()
            ->getMock();
        $quote->expects($this->any())->method('getCustomerId')->willReturn(1);
        $this->formKeyValidator->expects($this->once())->method('validate')->with($this->request)->willReturn('true');
        $this->quoteRepository->expects($this->once())->method('get')->willReturn($quote);
        $this->customerRestriction->expects($this->atLeastOnce())->method('canSubmit')->willReturn(true);
        $this->settingsProvider->expects($this->once())->method('getCurrentUserId')->willReturn(1);
        $this->request
            ->method('getParam')
            ->withConsecutive(['quote_id'], ['cart'])
            ->willReturnOnConsecutiveCalls(1, $cartData);
        $this->negotiableQuoteManagement->expects($this->once())->method('updateQuoteItems')->with(1, $cartData);
        $this->negotiableQuoteManagement->expects($this->once())
            ->method('updateProcessingByCustomerQuoteStatus')
            ->with(1);
        $this->messageManager->expects($this->once())
            ->method('addSuccessMessage')->willReturn(__('You have updated the items in the quote.'));
        $this->itemUpdateMock->execute();
    }
}
