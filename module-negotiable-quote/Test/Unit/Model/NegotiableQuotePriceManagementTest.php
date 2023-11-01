<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Model\NegotiableQuotePriceManagement;
use Magento\NegotiableQuote\Model\Quote\History;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\NegotiableQuote\Model\Validator\ValidatorInterface;
use Magento\NegotiableQuote\Model\Validator\ValidatorInterfaceFactory;
use Magento\NegotiableQuote\Model\Validator\ValidatorResult;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\ResourceModel\Quote\Collection;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for NegotiableQuotePriceManagement model.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class NegotiableQuotePriceManagementTest extends TestCase
{
    /**
     * @var NegotiableQuoteRepositoryInterface|MockObject
     */
    private $negotiableQuoteRepository;

    /**
     * @var ValidatorInterfaceFactory|MockObject
     */
    private $validatorFactory;

    /**
     * @var NegotiableQuoteItemManagementInterface|MockObject
     */
    private $negotiableQuotItemManagement;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var RestrictionInterface|MockObject
     */
    private $restriction;

    /**
     * @var History|MockObject
     */
    private $quoteHistory;

    /**
     * @var CollectionFactory|MockObject
     */
    private $quoteCollectionFactory;

    /**
     * @var NegotiableQuotePriceManagement
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->negotiableQuoteRepository = $this->getMockBuilder(
            NegotiableQuoteRepositoryInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->validatorFactory = $this->getMockBuilder(
            ValidatorInterfaceFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->negotiableQuotItemManagement = $this->getMockBuilder(
            NegotiableQuoteItemManagementInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quoteRepository = $this->getMockBuilder(
            CartRepositoryInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->restriction = $this->getMockBuilder(
            RestrictionInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quoteHistory = $this->getMockBuilder(History::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteCollectionFactory = $this->getMockBuilder(
            CollectionFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            NegotiableQuotePriceManagement::class,
            [
                'negotiableQuoteRepository' => $this->negotiableQuoteRepository,
                'validatorFactory' => $this->validatorFactory,
                'negotiableQuotItemManagement' => $this->negotiableQuotItemManagement,
                'quoteRepository' => $this->quoteRepository,
                'restriction' => $this->restriction,
                'quoteHistory' => $this->quoteHistory,
                'quoteCollectionFactory' => $this->quoteCollectionFactory,
            ]
        );
    }

    /**
     * Test get method.
     *
     * @return void
     */
    public function testPricesUpdated()
    {
        $quoteIds = [1];
        $validator = $this->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $validateResult = $this->getMockBuilder(ValidatorResult::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quoteExtensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();
        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getQuoteId'])
            ->getMockForAbstractClass();
        $quoteCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteData = new DataObject();
        $this->validatorFactory->expects($this->once())
            ->method('create')
            ->with(['action' => 'edit'])
            ->willReturn($validator);
        $this->quoteRepository->expects($this->exactly(2))->method('get')->with(1)->willReturn($quote);
        $this->restriction->expects($this->once())->method('setQuote')->with($quote)->willReturnSelf();
        $validator->expects($this->once())->method('validate')->with(['quote' => $quote])->willReturn($validateResult);
        $validateResult->expects($this->once())->method('hasMessages')->willReturn(false);
        $quote->expects($this->once())->method('getExtensionAttributes')->willReturn($quoteExtensionAttributes);
        $quoteExtensionAttributes->expects($this->once())->method('getNegotiableQuote')->willReturn($negotiableQuote);
        $this->quoteCollectionFactory->expects($this->once())->method('create')->willReturn($quoteCollection);
        $quoteCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with('entity_id', ['in' => $quoteIds])
            ->willReturnSelf();
        $quoteCollection->expects($this->once())->method('getItems')->willReturn([$quote]);
        $quote->expects($this->once())->method('getId')->willReturn(1);
        $this->quoteHistory->expects($this->once())
            ->method('collectOldDataFromQuote')
            ->with($quote)
            ->willReturn($quoteData);
        $negotiableQuote->expects($this->once())
            ->method('setStatus')
            ->with(NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN)
            ->willReturnSelf();
        $negotiableQuote->expects($this->exactly(3))->method('getId')->willReturn(1);
        $negotiableQuote->expects($this->once())->method('getQuoteId')->willReturn(1);
        $this->negotiableQuotItemManagement->expects($this->once())
            ->method('recalculateOriginalPriceTax')
            ->with(1, true, true)
            ->willReturn(true);
        $this->quoteHistory->expects($this->once())->method('updateStatusLog')->with(1, true);
        $this->quoteHistory->expects($this->once())
            ->method('checkPricesAndDiscounts')
            ->with($quote, $quoteData)
            ->willReturn($quoteData);
        $this->negotiableQuoteRepository->expects($this->once())
            ->method('save')
            ->with($negotiableQuote)
            ->willReturn(true);

        $this->assertTrue($this->model->pricesUpdated($quoteIds));
    }

    /**
     * Test get method if quote doesn't exist.
     *
     * @return void
     */
    public function testGetWithInvalidQuoteId()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage('Requested quote is not found. Row ID: QuoteID = 9999');
        $quoteIds = [9999];
        $exception = new NoSuchEntityException();
        $validator = $this->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->validatorFactory->expects($this->once())
            ->method('create')
            ->with(['action' => 'edit'])
            ->willReturn($validator);
        $this->quoteRepository->expects($this->once())->method('get')->with(9999)->willThrowException($exception);

        $this->model->pricesUpdated($quoteIds);
    }

    /**
     * Test get method if quote is locked.
     *
     * @return void
     */
    public function testGetWithInvalidQuoteStatus()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage(
            'The quote 1 is currently locked and cannot be updated. Please check the quote status.'
        );
        $quoteIds = [1];
        $message = __(
            "The quote %quoteId is currently locked and cannot be updated. Please check the quote status.",
            ['quoteId' => 1]
        );
        $validator = $this->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $validateResult = $this->getMockBuilder(ValidatorResult::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validatorFactory->expects($this->once())
            ->method('create')
            ->with(['action' => 'edit'])
            ->willReturn($validator);
        $this->quoteRepository->expects($this->once())->method('get')->with(1)->willReturn($quote);
        $this->restriction->expects($this->once())->method('setQuote')->with($quote)->willReturnSelf();
        $validator->expects($this->once())->method('validate')->with(['quote' => $quote])->willReturn($validateResult);
        $validateResult->expects($this->once())->method('hasMessages')->willReturn(true);
        $validateResult->expects($this->once())->method('getMessages')->willReturn([$message]);

        $this->model->pricesUpdated($quoteIds);
    }
}
