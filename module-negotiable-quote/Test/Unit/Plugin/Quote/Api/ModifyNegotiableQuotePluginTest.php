<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Plugin\Quote\Api;

use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Model\Quote\History;
use Magento\NegotiableQuote\Model\Validator\ValidatorInterface;
use Magento\NegotiableQuote\Model\Validator\ValidatorInterfaceFactory;
use Magento\NegotiableQuote\Model\Validator\ValidatorResult;
use Magento\NegotiableQuote\Plugin\Quote\Api\ModifyNegotiableQuotePlugin;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\ResourceModel\Quote\Collection;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Magento\NegotiableQuote\Plugin\Quote\Api\ModifyNegotiableQuotePlugin class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ModifyNegotiableQuotePluginTest extends TestCase
{
    /**
     * @var CollectionFactory|MockObject
     */
    private $quoteCollectionFactory;

    /**
     * @var NegotiableQuoteRepositoryInterface|MockObject
     */
    private $negotiableQuoteRepository;

    /**
     * @var ValidatorInterfaceFactory|MockObject
     */
    private $validatorFactory;

    /**
     * @var CartInterface|MockObject
     */
    private $quote;

    /**
     * @var ValidatorResult|MockObject
     */
    private $result;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var NegotiableQuoteItemManagementInterface|MockObject
     */
    private $negotiableQuoteItemManagement;

    /**
     * @var History|MockObject
     */
    private $quoteHistory;

    /**
     * @var ModifyNegotiableQuotePlugin
     */
    private $plugin;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->quoteCollectionFactory = $this->getMockBuilder(
            CollectionFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->validatorFactory = $this->getMockBuilder(
            ValidatorInterfaceFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->negotiableQuoteRepository = $this->getMockBuilder(
            NegotiableQuoteRepositoryInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItemsCount'])
            ->getMockForAbstractClass();
        $this->result = $this->getMockBuilder(ValidatorResult::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->negotiableQuoteItemManagement = $this->getMockBuilder(
            NegotiableQuoteItemManagementInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quoteHistory = $this->getMockBuilder(History::class)
            ->disableOriginalConstructor()
            ->getMock();
        $data = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->plugin = $objectManagerHelper->getObject(
            ModifyNegotiableQuotePlugin::class,
            [
                'quoteRepository' => $this->quoteRepository,
                'validatorFactory' => $this->validatorFactory,
                'negotiableQuoteRepository' => $this->negotiableQuoteRepository,
                'quoteCollectionFactory' => $this->quoteCollectionFactory,
                'negotiableQuoteItemManagement' => $this->negotiableQuoteItemManagement,
                'quoteHistory' => $this->quoteHistory,
                'oldQuoteData' => [1 => $data],
            ]
        );
    }

    /**
     * Test beforeDeleteById method.
     *
     * @return void
     */
    public function testBeforeDeleteById()
    {
        $quoteId = 1;
        $itemId = 1;
        $subject = $this->getMockBuilder(CartItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->prepareMocksForBeforeMethods();
        $this->result->expects($this->once())->method('hasMessages')->willReturn(false);
        $this->quote->expects($this->once())->method('getItemsCount')->willReturn(3);

        $this->plugin->beforeDeleteById($subject, $quoteId, $itemId);
    }

    /**
     * Test beforeDeleteById method with 1 item in quote.
     *
     * @return void
     */
    public function testBeforeDeleteByIdWithOneItemInQuote()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage(
            'Cannot delete all items from a B2B quote. The quote must contain at least one item.'
        );
        $quoteId = 1;
        $itemId = 1;
        $subject = $this->getMockBuilder(CartItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->prepareMocksForBeforeMethods();
        $this->result->expects($this->once())->method('hasMessages')->willReturn(false);
        $this->quote->expects($this->once())->method('getItemsCount')->willReturn(1);

        $this->plugin->beforeDeleteById($subject, $quoteId, $itemId);
    }

    /**
     * Test beforeDeleteById method with invalid quote status.
     *
     * @return void
     */
    public function testBeforeDeleteByIdWithInvalidQuoteStatus()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage(
            'The quote 1 is currently locked and cannot be updated. Please check the quote status.'
        );
        $quoteId = 1;
        $itemId = 1;
        $message = __(
            'The quote %quoteId is currently locked and cannot be updated. Please check the quote status.',
            ['quoteId' => 1]
        );
        $subject = $this->getMockBuilder(CartItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->prepareMocksForBeforeMethods();
        $this->result->expects($this->once())->method('hasMessages')->willReturn(true);
        $this->result->expects($this->once())->method('getMessages')->willReturn([$message]);
        $this->quote->expects($this->once())->method('getItemsCount')->willReturn(3);

        $this->plugin->beforeDeleteById($subject, $quoteId, $itemId);
    }

    /**
     * Test before save.
     *
     * @return void
     */
    public function testBeforeSave()
    {
        $quoteId = 1;
        $subject = $this->getMockBuilder(CartItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $cartItem = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $cartItem->expects($this->once())->method('getQuoteId')->willReturn($quoteId);
        $this->prepareMocksForBeforeMethods();
        $this->result->expects($this->once())->method('hasMessages')->willReturn(false);

        $this->plugin->beforeSave($subject, $cartItem);
    }

    /**
     * Test beforeSave method with invalid quote status.
     *
     * @return void
     */
    public function testBeforeSaveWithInvalidQuoteStatus()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage(
            'The quote 1 is currently locked and cannot be updated. Please check the quote status.'
        );
        $quoteId = 1;
        $subject = $this->getMockBuilder(CartItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $cartItem = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $message = __(
            'The quote %quoteId is currently locked and cannot be updated. Please check the quote status.',
            ['quoteId' => 1]
        );
        $cartItem->expects($this->once())->method('getQuoteId')->willReturn($quoteId);
        $this->prepareMocksForBeforeMethods();
        $this->result->expects($this->once())->method('hasMessages')->willReturn(true);
        $this->result->expects($this->once())->method('getMessages')->willReturn([$message]);

        $this->plugin->beforeSave($subject, $cartItem);
    }

    /**
     * Test afterDeleteById method.
     *
     * @return void
     */
    public function testAfterDeleteById()
    {
        $quoteId = 1;
        $subject = $this->getMockBuilder(CartItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote', 'setNegotiableQuote'])
            ->getMockForAbstractClass();
        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $data = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteRepository->expects($this->once())->method('get')->with($quoteId)->willReturn($this->quote);
        $this->quote->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $extensionAttributes->expects($this->atLeastOnce())->method('getNegotiableQuote')->willReturn($negotiableQuote);
        $negotiableQuote->expects($this->atLeastOnce())->method('getIsRegularQuote')->willReturn(true);
        $negotiableQuote->expects($this->once())
            ->method('setStatus')
            ->with(NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN)
            ->willReturnSelf();
        $negotiableQuote->expects($this->atLeastOnce())->method('getQuoteId')->willReturn($quoteId);
        $this->negotiableQuoteItemManagement->expects($this->once())
            ->method('recalculateOriginalPriceTax')
            ->with(1, true, true)
            ->willReturn(true);
        $this->quoteHistory->expects($this->once())->method('updateStatusLog')->with(1, true);
        $this->quoteHistory->expects($this->once())
            ->method('checkPricesAndDiscounts')
            ->with($this->quote, $data)
            ->willReturn($data);
        $this->negotiableQuoteRepository->expects($this->once())
            ->method('save')
            ->with($negotiableQuote)
            ->willReturn(true);

        $this->assertTrue($this->plugin->afterDeleteById($subject, true, $quoteId));
    }

    /**
     * Test afterSave method.
     *
     * @return void
     */
    public function testAfterSave()
    {
        $quoteId = 1;
        $subject = $this->getMockBuilder(CartItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $result = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $cartItem = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote', 'setNegotiableQuote'])
            ->getMockForAbstractClass();
        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $data = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cartItem->expects($this->once())->method('getQuoteId')->willReturn($quoteId);
        $this->quoteRepository->expects($this->once())->method('get')->with($quoteId)->willReturn($this->quote);
        $this->quote->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $extensionAttributes->expects($this->atLeastOnce())->method('getNegotiableQuote')->willReturn($negotiableQuote);
        $negotiableQuote->expects($this->atLeastOnce())->method('getIsRegularQuote')->willReturn(true);
        $negotiableQuote->expects($this->once())
            ->method('setStatus')
            ->with(NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN)
            ->willReturnSelf();
        $negotiableQuote->expects($this->atLeastOnce())->method('getQuoteId')->willReturn($quoteId);
        $this->negotiableQuoteItemManagement->expects($this->once())
            ->method('recalculateOriginalPriceTax')
            ->with(1, true, true)
            ->willReturn(true);
        $this->quoteHistory->expects($this->once())->method('updateStatusLog')->with(1, true);
        $this->quote->expects($this->once())->method('getId')->willReturn($quoteId);
        $this->quoteHistory->expects($this->once())
            ->method('checkPricesAndDiscounts')
            ->with($this->quote, $data)
            ->willReturn($data);
        $this->negotiableQuoteRepository->expects($this->once())
            ->method('save')
            ->with($negotiableQuote)
            ->willReturn(true);

        $this->assertEquals($result, $this->plugin->afterSave($subject, $result, $cartItem));
    }

    /**
     * Prepare mocks for "before" methods.
     *
     * @return void
     */
    private function prepareMocksForBeforeMethods()
    {
        $quoteId = 1;
        $quoteCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $oldQuoteData = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote', 'setNegotiableQuote'])
            ->getMockForAbstractClass();
        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $validator = $this->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quoteRepository->expects($this->once())->method('get')->with($quoteId)->willReturn($this->quote);
        $this->quoteCollectionFactory->expects($this->once())->method('create')->willReturn($quoteCollection);
        $quoteCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with('entity_id', $quoteId)
            ->willReturnSelf();
        $quoteCollection->expects($this->once())->method('getFirstItem')->willReturn($this->quote);
        $this->quoteHistory->expects($this->once())
            ->method('collectOldDataFromQuote')
            ->with($this->quote)
            ->willReturn($oldQuoteData);
        $this->quote->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $extensionAttributes->expects($this->atLeastOnce())->method('getNegotiableQuote')->willReturn($negotiableQuote);
        $negotiableQuote->expects($this->atLeastOnce())->method('getIsRegularQuote')->willReturn(true);
        $this->validatorFactory->expects($this->once())
            ->method('create')
            ->with(['action' => 'edit'])
            ->willReturn($validator);
        $validator->expects($this->once())
            ->method('validate')
            ->with(['quote' => $this->quote])
            ->willReturn($this->result);
    }
}
