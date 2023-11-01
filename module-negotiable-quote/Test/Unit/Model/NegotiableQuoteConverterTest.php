<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterfaceFactory;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteItemInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteItemInterfaceFactory;
use Magento\NegotiableQuote\Model\NegotiableQuoteConverter;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartInterfaceFactory;
use Magento\Quote\Api\Data\CartItemExtensionInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\ResourceModel\Quote\Item\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for NegotiableQuoteConverter class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class NegotiableQuoteConverterTest extends TestCase
{
    /**
     * @var CartInterfaceFactory|MockObject
     */
    private $cartFactory;

    /**
     * @var ProductInterfaceFactory|MockObject
     */
    private $productFactory;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * @var ExtensionAttributesFactory|MockObject
     */
    private $extensionFactory;

    /**
     * @var CartItemInterfaceFactory|MockObject
     */
    private $cartItemFactory;

    /**
     * @var NegotiableQuoteItemInterfaceFactory|MockObject
     */
    private $negotiableQuoteItemFactory;

    /**
     * @var NegotiableQuoteInterfaceFactory|MockObject
     */
    private $negotiableQuoteFactory;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder|MockObject
     */
    private $filterBuilder;

    /**
     * @var NegotiableQuoteConverter
     */
    private $negotiableQuoteConverter;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->cartFactory = $this->getMockBuilder(CartInterfaceFactory::class)->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->productFactory = $this->getMockBuilder(ProductInterfaceFactory::class)->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->productRepository = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->extensionFactory = $this->getMockBuilder(ExtensionAttributesFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->cartItemFactory = $this->getMockBuilder(CartItemInterfaceFactory::class)->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->negotiableQuoteItemFactory = $this->getMockBuilder(NegotiableQuoteItemInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->negotiableQuoteFactory = $this->getMockBuilder(NegotiableQuoteInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->filterBuilder = $this->getMockBuilder(FilterBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->negotiableQuoteConverter = $objectManager->getObject(
            NegotiableQuoteConverter::class,
            [
                'cartFactory' => $this->cartFactory,
                'productFactory' => $this->productFactory,
                'productRepository' => $this->productRepository,
                'extensionFactory' => $this->extensionFactory,
                'cartItemFactory' => $this->cartItemFactory,
                'negotiableQuoteItemFactory' => $this->negotiableQuoteItemFactory,
                'negotiableQuoteFactory' => $this->negotiableQuoteFactory,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'filterBuilder' => $this->filterBuilder
            ]
        );
    }

    /**
     * Test for quoteToArray method.
     *
     * @return void
     */
    public function testQuoteToArray(): void
    {
        $quoteData = [
            'quote_id' => 1
        ];
        $negotiableQuoteData = [
            'snapshot' => [],
            'items' => []
        ];
        $addressData = ['city' => 'New York'];
        $itemData = ['item_id' => 10];
        $itemOptionData = ['value' => 'option value'];
        $productData = ['name' => 'product name'];
        $quote = $this->getMockBuilder(CartInterface::class)->disableOriginalConstructor()
            ->onlyMethods(['getBillingAddress'])
            ->addMethods(['getData', 'getShippingAddress', 'getItemsCollection'])
            ->getMockForAbstractClass();
        $quote->expects($this->once())->method('getData')->willReturn($quoteData);
        $quoteExtensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)->disableOriginalConstructor()
            ->addMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();
        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)->disableOriginalConstructor()
            ->addMethods(['getData'])
            ->getMockForAbstractClass();
        $quote->expects($this->once())->method('getExtensionAttributes')->willReturn($quoteExtensionAttributes);
        $quoteExtensionAttributes->expects($this->once())->method('getNegotiableQuote')->willReturn($negotiableQuote);
        $negotiableQuote->expects($this->once())->method('getData')->willReturn($negotiableQuoteData);
        $address = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()
            ->addMethods(['getData'])
            ->getMockForAbstractClass();
        $quote->expects($this->atLeastOnce())->method('getShippingAddress')->willReturn($address);
        $quote->expects($this->atLeastOnce())->method('getBillingAddress')->willReturn($address);
        $address->expects($this->atLeastOnce())->method('getData')->willReturn($addressData);
        $quoteItem = $this->getMockBuilder(CartItemInterface::class)->disableOriginalConstructor()
            ->addMethods(['getData', 'getOptions'])
            ->getMockForAbstractClass();
        $quote->expects($this->once())->method('getItemsCollection')->willReturn([$quoteItem]);
        $quoteItem->expects($this->once())->method('getData')->willReturn($itemData);
        $quoteItemExtensionAttributes = $this->getMockBuilder(CartItemExtensionInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getNegotiableQuoteItem'])
            ->getMockForAbstractClass();
        $negotiableQuoteItem = $this->getMockBuilder(NegotiableQuoteItemInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getData'])
            ->getMockForAbstractClass();
        $quoteItem->expects($this->atLeastOnce())
            ->method('getExtensionAttributes')->willReturn($quoteItemExtensionAttributes);
        $quoteItemExtensionAttributes->expects($this->atLeastOnce())
            ->method('getNegotiableQuoteItem')->willReturn($negotiableQuoteItem);
        $negotiableQuoteItem->expects($this->once())->method('getData')->willReturn($itemData);
        $quoteItemOption = $this->getMockBuilder(OptionInterface::class)->disableOriginalConstructor()
            ->addMethods(['getData', 'getProduct'])
            ->getMockForAbstractClass();
        $quoteItem->expects($this->once())->method('getOptions')->willReturn([$quoteItemOption]);
        $quoteItemOption->expects($this->once())->method('getData')->willReturn($itemOptionData);
        $product = $this->getMockBuilder(ProductInterface::class)->disableOriginalConstructor()
            ->addMethods(['getData'])
            ->getMockForAbstractClass();
        $quoteItemOption->expects($this->exactly(2))->method('getProduct')->willReturn($product);
        $product->expects($this->once())->method('getData')->willReturn($productData);
        $this->assertEquals(
            [
                'quote' => $quoteData,
                'negotiable_quote' => [],
                'shipping_address' => $addressData,
                'billing_address' => $addressData,
                'items' => [
                    $itemData +
                    [
                        'negotiable_quote_item' => $itemData,
                        'options' => [$itemOptionData + ['product' => $productData]],
                    ]
                ]
            ],
            $this->negotiableQuoteConverter->quoteToArray($quote)
        );
    }

    /**
     * Test for arrayToQuote method.
     *
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testArrayToQuote(): void
    {
        $data = [
            'quote' => ['quote_id' => 1],
            'shipping_address' => ['city' => 'New York'],
            'billing_address' => ['city' => 'Chicago'],
            'negotiable_quote' => [],
            'items' => [
                [
                    'product_id' => 20,
                    'negotiable_quote_item' => ['item_id' => 30],
                    'options' => [
                        [
                            'product' => ['entity_id' => 20],
                            'value' => 'option_value'
                        ]
                    ]
                ],
                ['product_id' => 21]
            ]
        ];
        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'setData',
                    'removeAllAddresses',
                    'getShippingAddress',
                    'getBillingAddress',
                    'removeAllItems',
                    'getItemsCollection',
                    'setExtensionAttributes'
                ]
            )
            ->addMethods(['setTotalsCollectedFlag'])
            ->getMock();
        $this->cartFactory->expects($this->once())->method('create')->willReturn($quote);
        $quote->expects($this->once())->method('setData')->with($data['quote'])->willReturnSelf();
        $quote->expects($this->once())->method('removeAllAddresses')->willReturnSelf();
        $address = $this->getMockBuilder(AddressInterface::class)->disableOriginalConstructor()
            ->addMethods(['setData'])
            ->getMockForAbstractClass();
        $quote->expects($this->once())->method('getShippingAddress')->willReturn($address);
        $quote->expects($this->once())->method('getBillingAddress')->willReturn($address);
        $address->expects($this->atLeastOnce())->method('setData')
            ->withConsecutive([$data['shipping_address']], [$data['billing_address']])->willReturnSelf();
        $quote->expects($this->once())->method('removeAllItems')->willReturnSelf();
        $itemsCollection = $this->getMockBuilder(Collection::class)->disableOriginalConstructor()
            ->onlyMethods(['removeAllItems', 'addItem', 'getItems'])
            ->addMethods(['setData'])
            ->getMock();
        $quote->expects($this->once())->method('getItemsCollection')->willReturn($itemsCollection);
        $itemsCollection->expects($this->once())->method('removeAllItems')->willReturnSelf();
        $this->filterBuilder->method('setField')
            ->withConsecutive(['entity_id'], ['entity_id'])
            ->willReturnOnConsecutiveCalls($this->filterBuilder, $this->filterBuilder);
        $this->filterBuilder->method('setValue')
            ->withConsecutive([$data['items'][0]['product_id']], [$data['items'][1]['product_id']])
            ->willReturnOnConsecutiveCalls($this->filterBuilder, $this->filterBuilder);
        $this->filterBuilder->method('setConditionType')
            ->withConsecutive(['eq'], ['eq'])
            ->willReturnOnConsecutiveCalls($this->filterBuilder, $this->filterBuilder);
        $filter1 = $this->createMock(Filter::class);
        $filter2 = $this->createMock(Filter::class);
        $this->filterBuilder->expects($this->atLeastOnce())
            ->method('create')->willReturnOnConsecutiveCalls($filter1, $filter2);
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilters')->with([$filter1, $filter2])->willReturnSelf();
        $searchCriteria = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $searchResults = $this->getMockBuilder(SearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productRepository->expects($this->once())
            ->method('getList')->with($searchCriteria)->willReturn($searchResults);
        $searchResults->expects($this->once())->method('getTotalCount')->willReturn(1);
        $product = $this->getMockBuilder(ProductInterface::class)->disableOriginalConstructor()
            ->addMethods(['setData'])
            ->getMockForAbstractClass();
        $searchResults->expects($this->once())->method('getItems')->willReturn([$product]);
        $product->expects($this->once())->method('getId')->willReturn($data['items'][0]['product_id']);
        $quoteItem = $this->getMockBuilder(Item::class)->disableOriginalConstructor()
            ->onlyMethods(['setData', 'setQuote', 'addOption', 'setExtensionAttributes'])
            ->getMock();
        $this->cartItemFactory->expects($this->once())->method('create')->willReturn($quoteItem);
        $quoteItem->expects($this->once())
            ->method('setData')->with(['product_id' => $data['items'][0]['product_id']])->willReturnSelf();
        $quoteItem->expects($this->once())->method('setQuote')->with($quote)->willReturnSelf();
        $this->productFactory->expects($this->once())->method('create')->willReturn($product);
        $product->expects($this->once())
            ->method('setData')->with($data['items'][0]['options'][0]['product'])->willReturnSelf();
        $quoteItem->expects($this->once())->method('addOption')->willReturnSelf();
        $negotiableQuoteItem = $this->getMockBuilder(NegotiableQuoteItemInterface::class)->disableOriginalConstructor()
            ->addMethods(['setData'])
            ->getMockForAbstractClass();
        $this->negotiableQuoteItemFactory->expects($this->once())->method('create')->willReturn($negotiableQuoteItem);
        $negotiableQuoteItem->expects($this->once())->method('setData')->with()->willReturnSelf();
        $quoteItemExtensionAttributes = $this->getMockBuilder(CartItemExtensionInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['setNegotiableQuoteItem'])
            ->getMockForAbstractClass();
        $quoteExtensionAttributes = $this->getMockBuilder(CartExtensionInterface::class)->disableOriginalConstructor()
            ->addMethods(['setNegotiableQuote'])
            ->getMockForAbstractClass();
        $this->extensionFactory->expects($this->atLeastOnce())->method('create')
            ->withConsecutive([get_class($quoteItem)], [get_class($quote)])
            ->willReturnOnConsecutiveCalls($quoteItemExtensionAttributes, $quoteExtensionAttributes);
        $quoteItemExtensionAttributes->expects($this->once())
            ->method('setNegotiableQuoteItem')->with($negotiableQuoteItem)->willReturnSelf();
        $quoteItem->expects($this->once())
            ->method('setExtensionAttributes')->with($quoteItemExtensionAttributes)->willReturnSelf();
        $itemsCollection->expects($this->once())->method('addItem')->with($quoteItem)->willReturnSelf();
        $itemsCollection->expects($this->once())
            ->method('getItems')
            ->willReturn([$quoteItem]);
        $negotiableQuote = $this->getMockBuilder(NegotiableQuoteInterface::class)->disableOriginalConstructor()
            ->addMethods(['setData'])
            ->getMockForAbstractClass();
        $this->negotiableQuoteFactory->expects($this->once())->method('create')->willReturn($negotiableQuote);
        $negotiableQuote->expects($this->once())->method('setData')->with($data['negotiable_quote'])->willReturnSelf();
        $quoteExtensionAttributes->expects($this->once())
            ->method('setNegotiableQuote')->with($negotiableQuote)->willReturnSelf();
        $quote->expects($this->once())
            ->method('setExtensionAttributes')->with($quoteExtensionAttributes)->willReturnSelf();
        $quote->expects($this->once())->method('setTotalsCollectedFlag')->with(false)->willReturnSelf();
        $this->assertEquals($quote, $this->negotiableQuoteConverter->arrayToQuote($data));
    }
}
