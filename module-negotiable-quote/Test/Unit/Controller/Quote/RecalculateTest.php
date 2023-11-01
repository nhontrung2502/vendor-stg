<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Quote;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Layout;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Controller\Quote\Recalculate;
use Magento\NegotiableQuote\Model\Discount\StateChanges\Provider;
use Magento\NegotiableQuote\Model\Quote\Address;
use Magento\NegotiableQuote\Model\Quote\Currency;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Recalculate controller.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RecalculateTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var Recalculate
     */
    private $recalculate;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepositoryMock;

    /**
     * @var RestrictionInterface|MockObject
     */
    private $customerRestrictionMock;

    /**
     * @var NegotiableQuoteManagementInterface|MockObject
     */
    private $negotiableQuoteManagementMock;

    /**
     * @var Provider|MockObject
     */
    private $messageProviderMock;

    /**
     * @var Address|MockObject
     */
    private $negotiableQuoteAddressMock;

    /**
     * @var Currency|MockObject
     */
    private $quoteCurrencyMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var ResultFactory|MockObject
     */
    protected $resultFactory;

    /**
     * @var  CartInterface|MockObject
     */
    private $quote;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var ResultInterface|MockObject
     */
    private $resultJson;

    /**
     * @var NegotiableQuoteInterface|MockObject
     */
    private $negotiableQuote;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->quoteRepositoryMock = $this->getMockBuilder(CartRepositoryInterface::class)
            ->onlyMethods(['get', 'save'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerRestrictionMock = $this
            ->getMockBuilder(RestrictionInterface::class)
            ->onlyMethods(['isOwner', 'isSubUserContent', 'canSubmit'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->negotiableQuoteManagementMock = $this
            ->getMockBuilder(NegotiableQuoteManagementInterface::class)
            ->onlyMethods(['recalculateQuote'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->messageProviderMock = $this
            ->getMockBuilder(Provider::class)
            ->onlyMethods(['getChangesMessages'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->negotiableQuoteAddressMock = $this->getMockBuilder(Address::class)
            ->onlyMethods(['updateQuoteShippingAddressDraft'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteCurrencyMock = $this->getMockBuilder(Currency::class)
            ->onlyMethods(['updateQuoteCurrency'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->onlyMethods(['getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->onlyMethods(['addError'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->resultJson = $this->getMockBuilder(ResultInterface::class)
            ->addMethods(['setJsonData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->negotiableQuote = $this
            ->getMockBuilder(NegotiableQuoteInterface::class)
            ->onlyMethods(['getIsRegularQuote', 'getStatus', 'getNegotiatedPriceValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->recalculate = $this->objectManagerHelper->getObject(
            Recalculate::class,
            [
                'quoteRepository' => $this->quoteRepositoryMock,
                'customerRestriction' => $this->customerRestrictionMock,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagementMock,
                'messageProvider' => $this->messageProviderMock,
                'negotiableQuoteAddress' => $this->negotiableQuoteAddressMock,
                'quoteCurrency' => $this->quoteCurrencyMock,
                '_request' => $this->request,
                'resultFactory' => $this->resultFactory,
                'messageManager' => $this->messageManager
            ]
        );
    }

    /**
     * Prepare Request Mock.
     *
     * @return void
     */
    private function prepareRequestMock(): void
    {
        $quoteId = 234;
        $this->request->expects($this->once())->method('getParam')->with('quote_id')->willReturn($quoteId);
    }

    /**
     * Prepare Negotiable Quote mock.
     *
     * @return void
     */
    private function prepareNegotiableQuoteMock(): void
    {
        $isRegularQuote = true;
        $this->negotiableQuote->expects($this->once())->method('getIsRegularQuote')->willReturn($isRegularQuote);
        $negotiatedPrice = 234;
        $this->negotiableQuote->expects($this->once())->method('getNegotiatedPriceValue')
            ->willReturn($negotiatedPrice);
        $negotiableQuoteStatus = NegotiableQuoteInterface::STATUS_EXPIRED;
        $this->negotiableQuote->expects($this->once())->method('getStatus')->willReturn($negotiableQuoteStatus);
    }

    /**
     * Prepare Quote mock.
     *
     * @return void
     */
    private function prepareQuoteMock(): void
    {
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->addMethods(['getNegotiableQuote', 'setShippingAssignments'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $extensionAttributes->expects($this->atLeastOnce())->method('getNegotiableQuote')
            ->willReturn($this->negotiableQuote);
        $this->quote = $this->getMockBuilder(CartInterface::class)
            ->onlyMethods(['getExtensionAttributes'])
            ->addMethods(['getBaseCurrencyCode', 'getQuoteCurrencyCode'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quote->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($extensionAttributes);
    }

    /**
     * Test execute() method.
     *
     * @return void
     */
    public function testExecute(): void
    {
        $this->prepareRequestMock();
        $this->prepareNegotiableQuoteMock();
        $this->prepareQuoteMock();
        $this->quoteRepositoryMock->expects($this->atLeastOnce())->method('get')->willReturn($this->quote);
        $this->customerRestrictionMock->expects($this->exactly(3))->method('isOwner')
            ->willReturnOnConsecutiveCalls(false, true, true);
        $this->customerRestrictionMock->expects($this->once())->method('isSubUserContent')->willReturn(true);
        $this->customerRestrictionMock->expects($this->once())->method('canSubmit')->willReturn(true);
        $this->customerRestrictionMock->expects($this->once())->method('canCurrencyUpdate')->willReturn(true);
        $this->quoteCurrencyMock->expects($this->once())->method('updateQuoteCurrency')->willReturn($this->quote);
        $this->negotiableQuoteAddressMock->expects($this->once())
            ->method('updateQuoteShippingAddressDraft')->willReturn($this->quote);
        $this->negotiableQuoteManagementMock->expects($this->once())->method('recalculateQuote');
        $resultPage = $this->getMockBuilder(ResultInterface::class)
            ->addMethods(['addHandle', 'getLayout'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $resultPage->expects($this->exactly(2))
            ->method('addHandle')->with('negotiable_quote_quote_view')->willReturnSelf();
        $layout = $this->getMockBuilder(Layout::class)
            ->onlyMethods(['getBlock'])
            ->disableOriginalConstructor()
            ->getMock();
        $block = $this->getMockBuilder(AbstractBlock::class)
            ->onlyMethods(['toHtml'])
            ->addMethods(['setAdditionalMessage', 'setIsRecalculated'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $block->expects($this->exactly(2))->method('setAdditionalMessage');
        $block->expects($this->once())->method('setIsRecalculated');
        $blockHtml = '<span>test Message</span>';
        $block->expects($this->exactly(3))->method('toHtml')->willReturn($blockHtml);
        $layout->method('getBlock')
            ->withConsecutive(['quote.message'], ['quote_items'], ['quote.message'], ['quote.address'])
            ->willReturnOnConsecutiveCalls($block, $block, $block, $block);
        $resultPage->expects($this->exactly(2))->method('getLayout')->willReturn($layout);
        $this->resultJson->expects($this->once())->method('setJsonData')->willReturnSelf();
        $this->resultFactory->method('create')
            ->withConsecutive([ResultFactory::TYPE_PAGE], [ResultFactory::TYPE_JSON])
            ->willReturnOnConsecutiveCalls($resultPage, $this->resultJson);
        $notifications = ['Test notification'];
        $this->messageProviderMock->expects($this->once())->method('getChangesMessages')
            ->willReturn($notifications);
        $this->quoteRepositoryMock->expects($this->once())->method('save');
        $this->quote->expects($this->exactly(2))->method('getBaseCurrencyCode')->willReturn('USD');
        $this->quote->expects($this->exactly(2))->method('getQuoteCurrencyCode')->willReturn('EUR');

        $this->assertEquals($this->resultJson, $this->recalculate->execute());
    }

    /**
     * Test execute() method with Exception.
     *
     * @return void
     */
    public function testExecuteWithException(): void
    {
        $this->prepareRequestMock();
        $this->customerRestrictionMock->expects($this->once())->method('isOwner')->willReturn(false);
        $this->customerRestrictionMock->expects($this->once())->method('isSubUserContent')->willReturn(false);
        $this->resultJson->expects($this->once())->method('setJsonData')->willReturnSelf();
        $this->quoteRepositoryMock->expects($this->once())->method('get')->willReturn($this->quote);
        $this->resultFactory->expects($this->once())->method('create')
            ->with(ResultFactory::TYPE_JSON)->willReturn($this->resultJson);
        $this->messageManager->expects($this->once())->method('addError')->willReturnSelf();

        $this->assertEquals($this->resultJson, $this->recalculate->execute());
    }
}
