<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\NegotiableQuote as NegotiableQuoteModel;
use Magento\NegotiableQuote\Model\ResourceModel\NegotiableQuote as NegotiableQuote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class NegotiableQuoteTest
 *
 * Test saving the data of negotiable quote
 */
class NegotiableQuoteTest extends TestCase
{
    /**
     * @var Context|MockObject
     */
    protected $context;

    /**
     * @var NegotiableQuote|MockObject
     */
    protected $negotiableQuoteMock;

    /**
     * @var AdapterInterface|MockObject
     */
    protected $connection;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $resource = $this->createMock(ResourceConnection::class);
        $this->connection = $this->createMock(AdapterInterface::class);
        $resource->expects($this->any())->method('getConnection')->willReturn($this->connection);
        $this->context->expects($this->any())->method('getResources')->willReturn($resource);
        $objectManager = new ObjectManager($this);
        $this->negotiableQuoteMock = $objectManager->getObject(
            NegotiableQuote::class,
            [
                'context' => $this->context,
            ]
        );
    }

    /**
     * Test saveNegotiatedQuoteData()
     *
     * @dataProvider dataProvider
     * @param array $negotiableQuote
     * @throws CouldNotSaveException
     */
    public function testSaveNegotiatedQuoteData(array $negotiableQuote): void
    {
        $quote = $this->createMock(NegotiableQuoteModel::class);
        $quote->expects($this->any())
            ->method('getData')
            ->willReturn($negotiableQuote);

        $this->assertInstanceOf(
            NegotiableQuote::class,
            $this->negotiableQuoteMock->saveNegotiatedQuoteData($quote)
        );
    }

    /**
     * Test saveNegotiatedQuoteData() with exception
     */
    public function testSaveNegotiatedQuoteDataException(): void
    {
        $this->connection->expects($this->any())
            ->method('insertOnDuplicate')
            ->with(null, ['bad array'], [0])
            ->willThrowException(new \Exception(''));
        $quote = $this->createMock(NegotiableQuoteModel::class);
        $quote->expects($this->any())->method('getData')->willReturn(['bad array']);

        $this->expectException(CouldNotSaveException::class);
        $this->negotiableQuoteMock->saveNegotiatedQuoteData($quote);
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            'test with quote data without extension attributes' => [
                'quote' => [
                    'quote_id' => '00001',
                    'quote_name' => 'new quote',
                    'status' => 'created',
                    'is_regular_quote' => true,
                    'applied_rule_ids' => '',
                    'original_total_price' => 5.0,
                    'base_original_total_price' => 5.0,
                    'base_negotiated_total_price' => 5.0,
                    'negotiated_total_price' => 5.0,
                    'negotiated_price_type' => null,
                    'creator_id' => '1',
                    'creator_type' => 3
                ]
            ],
            'test with quote data with extension attributes' => [
                'quote' => [
                        'quote_id' => '00002',
                        'quote_name' => 'test',
                        'status' => 'created',
                        'is_regular_quote' => '1',
                        'negotiated_price_type' => null,
                        'negotiated_price_value' => null,
                        'shipping_price' => null,
                        'expiration_period' => null,
                        'status_email_notification' => '0',
                        'snapshot' => null,
                        'has_unconfirmed_changes' => '0',
                        'is_customer_price_changed' => '0',
                        'is_shipping_tax_changed' => '0',
                        'notifications' => null,
                        'applied_rule_ids' => '',
                        'is_address_draft' => '0',
                        'deleted_sku' => null,
                        'creator_type' => '3',
                        'creator_id' => '1',
                        'original_total_price' => '5.0000',
                        'base_original_total_price' => '5.0000',
                        'negotiated_total_price' => '5.0000',
                        'base_negotiated_total_price' => '5.0000',
                        'extension_attributes' => [
                                'attribute_one' => 1,
                                'attribute_two' => 'two'
                            ]
                    ]
            ]
        ];
    }
}
