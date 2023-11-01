<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Plugin\Quote\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Model\Validator\ValidatorInterface;
use Magento\NegotiableQuote\Model\Validator\ValidatorInterfaceFactory;
use Magento\NegotiableQuote\Model\Validator\ValidatorResult;
use Magento\NegotiableQuote\Plugin\Quote\Model\QuoteUpdateValidator;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\ResourceModel\Quote\Collection;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Magento\NegotiableQuote\Plugin\Quote\Model\QuoteUpdateValidator class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class QuoteUpdateValidatorTest extends TestCase
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
     * @var CartExtensionFactory|MockObject
     */
    private $cartExtensionFactory;

    /**
     * @var CartInterface|MockObject
     */
    private $quote;

    /**
     * @var CartInterface|MockObject
     */
    private $initialQuote;

    /**
     * @var NegotiableQuoteInterface|MockObject
     */
    private $negotiableQuote;

    /**
     * @var ValidatorResult|MockObject
     */
    private $result;

    /**
     * @var QuoteUpdateValidator
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
        $this->cartExtensionFactory = $this->getMockBuilder(
            CartExtensionFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerId', 'getCreatedAt', 'getStoreId', 'getShippingAddress'])
            ->getMockForAbstractClass();
        $this->initialQuote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerId', 'getCreatedAt', 'getStoreId'])
            ->getMockForAbstractClass();
        $this->negotiableQuote = $this->getMockBuilder(
            NegotiableQuoteInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->result = $this->getMockBuilder(ValidatorResult::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->plugin = $objectManagerHelper->getObject(
            QuoteUpdateValidator::class,
            [
                'quoteCollectionFactory' => $this->quoteCollectionFactory,
                'negotiableQuoteRepository' => $this->negotiableQuoteRepository,
                'validatorFactory' => $this->validatorFactory,
                'cartExtensionFactory' => $this->cartExtensionFactory,
            ]
        );
    }

    /**
     * Test beforeSave method.
     *
     * @return void
     */
    public function testBeforeSave()
    {
        $subject = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->prepareMocks();
        $this->result->expects($this->once())->method('getMessages')->willReturn([]);
        $this->quote->expects($this->atLeastOnce())->method('getCreatedAt')->willReturn('2017-04-05 11:59:59');
        $this->initialQuote->expects($this->once())->method('getCreatedAt')->willReturn('2017-04-05 11:59:59');
        $this->quote->expects($this->atLeastOnce())->method('getCustomerId')->willReturn(1);
        $this->initialQuote->expects($this->once())->method('getCustomerId')->willReturn(1);
        $this->quote->expects($this->atLeastOnce())->method('getStoreId')->willReturn(1);
        $this->initialQuote->expects($this->once())->method('getStoreId')->willReturn(1);
        $this->negotiableQuote->expects($this->once())->method('getShippingPrice')->willReturn(null);

        $this->plugin->beforeSave($subject, $this->quote);
    }

    /**
     * Test beforeSave without quote id.
     *
     * @return void
     */
    public function testBeforeSaveWithoutQuoteId()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage('"id" is required. Enter and try again.');
        $subject = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote', 'setNegotiableQuote'])
            ->getMockForAbstractClass();
        $this->quote->expects($this->atLeastOnce())->method('getId')->willReturn(null);
        $this->quote->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $extensionAttributes->expects($this->atLeastOnce())
            ->method('getNegotiableQuote')
            ->willReturn($this->negotiableQuote);

        $this->plugin->beforeSave($subject, $this->quote);
    }

    /**
     * Test beforeSave without invalid negotiable quote id.
     *
     * @return void
     */
    public function testBeforeSaveWithInvalidNegotiableQuoteId()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage('You cannot update the requested attribute. Row ID: quote_id = 2.');
        $quoteId = 1;
        $subject = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote', 'setNegotiableQuote'])
            ->getMockForAbstractClass();
        $this->quote->expects($this->atLeastOnce())->method('getId')->willReturn($quoteId);
        $this->quote->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $extensionAttributes->expects($this->atLeastOnce())
            ->method('getNegotiableQuote')
            ->willReturn($this->negotiableQuote);
        $this->negotiableQuote->expects($this->atLeastOnce())->method('getQuoteId')->willReturn(2);

        $this->plugin->beforeSave($subject, $this->quote);
    }

    /**
     * Test beforeSave with updating attribute that is not allowed to change.
     *
     * @return void
     */
    public function testBeforeSaveWithInvalidAttribute()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage(
            'You cannot update the requested attribute. Row ID: created_at = 2017-04-05 11:59:59.'
        );
        $subject = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->prepareMocks();
        $this->result->expects($this->once())->method('getMessages')->willReturn([]);
        $this->quote->expects($this->atLeastOnce())->method('getCreatedAt')->willReturn('2017-04-05 11:59:59');
        $this->initialQuote->expects($this->once())->method('getCreatedAt')->willReturn('2017-04-03 00:00:00');
        $this->quote->expects($this->atLeastOnce())->method('getCustomerId')->willReturn(1);
        $this->initialQuote->expects($this->once())->method('getCustomerId')->willReturn(1);
        $this->quote->expects($this->atLeastOnce())->method('getStoreId')->willReturn(1);
        $this->initialQuote->expects($this->once())->method('getStoreId')->willReturn(1);
        $this->negotiableQuote->expects($this->once())->method('getShippingPrice')->willReturn(null);

        $this->plugin->beforeSave($subject, $this->quote);
    }

    /**
     * Test beforeSave with shipping price but without shipping address.
     *
     * @return void
     */
    public function testBeforeSaveWithShippingAndWithoutShippingAddress()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage('Cannot set the shipping price. You must select a shipping method first.');
        $subject = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->prepareMocks();
        $this->result->expects($this->once())->method('getMessages')->willReturn([]);
        $this->quote->expects($this->atLeastOnce())->method('getCreatedAt')->willReturn('2017-04-05 11:59:59');
        $this->initialQuote->expects($this->once())->method('getCreatedAt')->willReturn('2017-04-05 11:59:59');
        $this->quote->expects($this->atLeastOnce())->method('getCustomerId')->willReturn(1);
        $this->initialQuote->expects($this->once())->method('getCustomerId')->willReturn(1);
        $this->quote->expects($this->atLeastOnce())->method('getStoreId')->willReturn(1);
        $this->initialQuote->expects($this->once())->method('getStoreId')->willReturn(1);
        $this->negotiableQuote->expects($this->once())->method('getShippingPrice')->willReturn(15);
        $this->quote->expects($this->atLeastOnce())->method('getShippingAddress')->willReturn($address);
        $address->expects($this->once())->method('getShippingMethod')->willReturn(null);

        $this->plugin->beforeSave($subject, $this->quote);
    }

    /**
     * Test beforeSave with incorrect quote status.
     *
     * @return void
     */
    public function testBeforeSaveWithIncorrectQuoteStatus()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage(
            'The quote 1 is currently locked and cannot be updated. Please check the quote status.'
        );
        $subject = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $message = __(
            'The quote %quoteId is currently locked and cannot be updated. Please check the quote status.',
            ['quoteId' => 1]
        );
        $this->prepareMocks();
        $this->result->expects($this->once())->method('getMessages')->willReturn([$message]);
        $this->quote->expects($this->atLeastOnce())->method('getCreatedAt')->willReturn('2017-04-05 11:59:59');
        $this->initialQuote->expects($this->once())->method('getCreatedAt')->willReturn('2017-04-05 11:59:59');
        $this->quote->expects($this->atLeastOnce())->method('getCustomerId')->willReturn(1);
        $this->initialQuote->expects($this->once())->method('getCustomerId')->willReturn(1);
        $this->quote->expects($this->atLeastOnce())->method('getStoreId')->willReturn(1);
        $this->initialQuote->expects($this->once())->method('getStoreId')->willReturn(1);
        $this->negotiableQuote->expects($this->once())->method('getShippingPrice')->willReturn(null);

        $this->plugin->beforeSave($subject, $this->quote);
    }

    /**
     * Prepare mocks.
     *
     * @return void
     */
    private function prepareMocks()
    {
        $quoteId = 1;
        $oldExtensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote', 'setNegotiableQuote'])
            ->getMockForAbstractClass();
        $extensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote', 'setNegotiableQuote'])
            ->getMockForAbstractClass();
        $oldNegotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quoteCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $validator = $this->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quote->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $this->quote->expects($this->atLeastOnce())->method('getId')->willReturn($quoteId);
        $extensionAttributes->expects($this->atLeastOnce())
            ->method('getNegotiableQuote')
            ->willReturn($this->negotiableQuote);
        $this->negotiableQuote->expects($this->atLeastOnce())->method('getQuoteId')->willReturn($quoteId);
        $this->quoteCollectionFactory->expects($this->atLeastOnce())->method('create')->willReturn($quoteCollection);
        $quoteCollection->expects($this->atLeastOnce())
            ->method('addFieldToFilter')
            ->with('entity_id', $quoteId)
            ->willReturnSelf();
        $quoteCollection->expects($this->atLeastOnce())->method('getFirstItem')->willReturn($this->initialQuote);
        $this->negotiableQuoteRepository->expects($this->atLeastOnce())
            ->method('getById')
            ->with($quoteId)
            ->willReturn($oldNegotiableQuote);
        $this->initialQuote->expects($this->atLeastOnce())
            ->method('getExtensionAttributes')
            ->willReturnOnConsecutiveCalls(
                null,
                $oldExtensionAttributes,
                $oldExtensionAttributes,
                null,
                $oldExtensionAttributes,
                $oldExtensionAttributes
            );
        $this->cartExtensionFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($oldExtensionAttributes);
        $this->initialQuote->expects($this->atLeastOnce())
            ->method('setExtensionAttributes')
            ->with($oldExtensionAttributes)
            ->willReturnSelf();
        $oldExtensionAttributes->expects($this->atLeastOnce())
            ->method('setNegotiableQuote')
            ->with($oldNegotiableQuote)
            ->willReturnSelf();
        $oldExtensionAttributes->expects($this->atLeastOnce())
            ->method('getNegotiableQuote')
            ->willReturn($oldNegotiableQuote);
        $oldNegotiableQuote->expects($this->once())->method('getIsRegularQuote')->willReturn(true);
        $this->negotiableQuote->expects($this->once())->method('setQuoteId')->with($quoteId)->willReturnSelf();
        $this->negotiableQuote->expects($this->once())->method('getStatus')->willReturn(null);
        $oldNegotiableQuote->expects($this->once())
            ->method('getStatus')
            ->willReturn(NegotiableQuoteInterface::STATUS_CREATED);
        $this->negotiableQuote->expects($this->once())
            ->method('setStatus')
            ->with(NegotiableQuoteInterface::STATUS_CREATED)
            ->willReturnSelf();
        $this->validatorFactory->expects($this->once())
            ->method('create')
            ->with(['action' => 'edit'])
            ->willReturn($validator);
        $validator->expects($this->once())
            ->method('validate')
            ->with(['quote' => $this->initialQuote])
            ->willReturn($this->result);
    }
}
