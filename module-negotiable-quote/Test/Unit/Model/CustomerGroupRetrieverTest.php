<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Group\RetrieverInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Model\CustomerGroupRetriever;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for class CustomerGroupRetriever.
 */
class CustomerGroupRetrieverTest extends TestCase
{
    /**
     * @var CustomerGroupRetriever
     */
    private $retriever;

    /**
     * @var RetrieverInterface|MockObject
     */
    private $customerGroupRetriever;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var NegotiableQuoteManagementInterface|MockObject
     */
    private $negotiableQuoteManagement;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->customerGroupRetriever = $this->getMockBuilder(RetrieverInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->negotiableQuoteManagement = $this->getMockBuilder(NegotiableQuoteManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->retriever = $objectManager->getObject(
            CustomerGroupRetriever::class,
            [
                'negotiableQuoteManagement' => $this->negotiableQuoteManagement,
                'request' => $this->request,
                'customerGroupRetriever' => $this->customerGroupRetriever,
            ]
        );
    }

    /**
     * Test for method getCustomerGroupId with request param.
     *
     * @return void
     */
    public function testGetCustomerGroupIdRequest()
    {
        $this->request->expects($this->atLeastOnce())->method('getParam')->with('quote_id')->willReturn(1);
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quote->expects($this->once())->method('getCustomer')->willReturn($customer);
        $customer->expects($this->once())->method('getGroupId')->willReturn(2);
        $this->negotiableQuoteManagement->expects($this->once())
            ->method('getNegotiableQuote')->with(1)->willReturn($quote);

        $this->assertEquals(2, $this->retriever->getCustomerGroupId());
    }

    /**
     * Test for method getCustomerGroupId with exception.
     *
     * @return void
     */
    public function testGetCustomerGroupIdException()
    {
        $this->request->expects($this->atLeastOnce())->method('getParam')->with('quote_id')->willReturn(1);

        $this->negotiableQuoteManagement->expects($this->once())
            ->method('getNegotiableQuote')->with(1)
            ->willThrowException(new NoSuchEntityException());
        $this->customerGroupRetriever->expects($this->once())->method('getCustomerGroupId')->willReturn(2);

        $this->assertEquals(2, $this->retriever->getCustomerGroupId());
    }

    /**
     * Test for method getCustomerGroupId without request params.
     *
     * @return void
     */
    public function testGetCustomerGroupIdWithoutRequest()
    {
        $this->request->expects($this->once())->method('getParam')->with('quote_id')->willReturn(null);

        $this->negotiableQuoteManagement->expects($this->never())
            ->method('getNegotiableQuote');
        $this->customerGroupRetriever->expects($this->once())->method('getCustomerGroupId')->willReturn(2);

        $this->assertEquals(2, $this->retriever->getCustomerGroupId());
    }
}
