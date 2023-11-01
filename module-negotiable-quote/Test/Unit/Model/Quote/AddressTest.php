<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Quote;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Model\Discount\StateChanges\Applier;
use Magento\NegotiableQuote\Model\Quote\Address;
use Magento\NegotiableQuote\Model\Quote\History;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\TotalsCollector;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Address.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AddressTest extends TestCase
{
    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepositoryMock;

    /**
     * @var TotalsCollector|MockObject
     */
    private $totalsCollectorMock;

    /**
     * @var RestrictionInterface|MockObject
     */
    private $restrictionMock;

    /**
     * @var NegotiableQuoteItemManagementInterface|MockObject
     */
    private $negotiableQuoteItemManagementMock;

    /**
     * @var NegotiableQuoteRepositoryInterface|MockObject
     */
    private $negotiableQuoteRepositoryMock;

    /**
     * @var History|MockObject
     */
    private $quoteHistoryMock;

    /**
     * @var Applier|MockObject
     */
    private $applierMock;

    /**
     * @var AddressRepositoryInterface|MockObject
     */
    private $addressRepositoryMock;

    /**
     * @var NegotiableQuoteManagementInterface|MockObject
     */
    private $negotiableQuoteManagementMock;

    /**
     * @var Address
     */
    private $address;

    /**
     * Set up.
     *
     * @return @void
     */
    protected function setUp(): void
    {
        $this->quoteRepositoryMock = $this
            ->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'get',
                'save'
            ])
            ->getMockForAbstractClass();
        $this->totalsCollectorMock = $this
            ->getMockBuilder(TotalsCollector::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'collectQuoteTotals',
                'collectAddressTotals'
            ])
            ->getMock();
        $this->restrictionMock = $this
            ->getMockBuilder(RestrictionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['canSubmit'])
            ->getMockForAbstractClass();
        $this->negotiableQuoteItemManagementMock = $this
            ->getMockBuilder(NegotiableQuoteItemManagementInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'recalculateOriginalPriceTax'
            ])
            ->getMockForAbstractClass();
        $this->negotiableQuoteRepositoryMock = $this
            ->getMockBuilder(NegotiableQuoteRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['save'])
            ->getMockForAbstractClass();
        $this->quoteHistoryMock = $this
            ->getMockBuilder(History::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'collectTaxDataFromQuote',
                'collectOldDataFromQuote',
                'checkPricesAndDiscounts',
                'checkTaxes'
            ])
            ->getMock();
        $taxDataMock = $this
            ->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteHistoryMock->expects($this->any())
            ->method('collectTaxDataFromQuote')
            ->willReturn($taxDataMock);
        $this->quoteHistoryMock->expects($this->any())
            ->method('collectOldDataFromQuote')
            ->willReturn($taxDataMock);
        $this->applierMock = $this
            ->getMockBuilder(Applier::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'setIsTaxChanged',
                'setIsAddressChanged'
            ])
            ->getMock();
        $this->addressRepositoryMock = $this
            ->getMockBuilder(AddressRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();
        $addressMock = $this
            ->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->addressRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($addressMock);
        $this->negotiableQuoteManagementMock = $this
            ->getMockBuilder(NegotiableQuoteManagementInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getNegotiableQuote',
                'updateProcessingByCustomerQuoteStatus'
            ])
            ->getMockForAbstractClass();
    }

    /**
     * Create testing object instance.
     *
     * @return void
     */
    private function createInstance()
    {
        $objectManager = new ObjectManager($this);
        $this->address = $objectManager->getObject(
            Address::class,
            [
                'quoteRepository' => $this->quoteRepositoryMock,
                'totalsCollector' => $this->totalsCollectorMock,
                'restriction' => $this->restrictionMock,
                'quoteItemManagement' => $this->negotiableQuoteItemManagementMock,
                'negotiableQuoteRepository' => $this->negotiableQuoteRepositoryMock,
                'quoteHistory' => $this->quoteHistoryMock,
                'messageApplier' => $this->applierMock,
                'addressRepository' => $this->addressRepositoryMock,
                'negotiableQuoteManagement' => $this->negotiableQuoteManagementMock
            ]
        );
    }

    /**
     * Test for updateQuoteShippingAddress() method when submitting is forbidden.
     *
     * @return void
     */
    public function testUpdateQuoteShippingAddressWhenSubmitForbidden()
    {
        $customerAddressMock = $this
            ->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->restrictionMock->expects($this->once())
            ->method('canSubmit')
            ->willReturn(false);

        $this->createInstance();
        $this->assertFalse($this->address->updateQuoteShippingAddress(1, $customerAddressMock));
    }

    /**
     * Test for updateQuoteShippingAddress() method.
     *
     * @param bool $isTaxChanged
     * @dataProvider dataProviderUpdateQuoteShippingAddress
     * @return void
     */
    public function testUpdateQuoteShippingAddress($isTaxChanged)
    {
        $customerAddressMock = $this
            ->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $quoteMock = $this->buildNegotiableQuoteMock();
        $quoteMock->expects($this->any())
            ->method('isVirtual')
            ->willReturn(true);
        $this->negotiableQuoteManagementMock->expects($this->once())
            ->method('getNegotiableQuote')
            ->willReturn($quoteMock);
        $this->restrictionMock->expects($this->once())
            ->method('canSubmit')
            ->willReturn(true);
        $resultTaxDataMock = $this
            ->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getIsTaxChanged',
                'getIsShippingTaxChanged'
            ])
            ->getMock();
        $resultTaxDataMock->expects($this->once())
            ->method('getIsTaxChanged')
            ->willReturn($isTaxChanged);
        $resultTaxDataMock->expects($this->any())
            ->method('getIsShippingTaxChanged')
            ->willReturn($isTaxChanged);
        $this->quoteHistoryMock->expects($this->any())
            ->method('checkTaxes')
            ->willReturn($resultTaxDataMock);

        $this->createInstance();
        $this->assertTrue($this->address->updateQuoteShippingAddress(1, $customerAddressMock));
    }

    /**
     * Test for updateQuoteShippingAddress() method with Exception.
     *
     * @return void
     */
    public function testUpdateQuoteShippingAddressException()
    {
        $this->expectException('Exception');
        $this->expectExceptionMessage('Unable to update shipping address');
        $customerAddressMock = $this
            ->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $quoteMock = $this
            ->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->negotiableQuoteManagementMock->expects($this->once())
            ->method('getNegotiableQuote')
            ->willReturn($quoteMock);
        $this->restrictionMock->expects($this->once())
            ->method('canSubmit')
            ->willReturn(true);
        $this->quoteHistoryMock->expects($this->once())
            ->method('collectTaxDataFromQuote')
            ->willThrowException(
                new \Exception()
            );

        $this->createInstance();
        $this->address->updateQuoteShippingAddress(1, $customerAddressMock);
    }

    /**
     * Build NegotiableQuote mock with its dependencies.
     *
     * @param bool $isAddressDraft [optional]
     * @param string $snapshot [optional]
     * @return MockObject
     */
    private function buildNegotiableQuoteMock($isAddressDraft = false, $snapshot = '[]')
    {
        $negotiableQuoteMock = $this
            ->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getIsAddressDraft',
                'getStatus',
                'setIsAddressDraft',
                'getNegotiatedPriceValue',
                'getSnapshot'
            ])
            ->getMockForAbstractClass();
        $negotiableQuoteMock->expects($this->any())
            ->method('getNegotiatedPriceValue')
            ->willReturn(null);
        $negotiableQuoteMock->expects($this->any())->method('getSnapshot')
            ->willReturn($snapshot);
        $negotiableQuoteMock->expects($this->any())->method('getIsAddressDraft')->willReturn($isAddressDraft);
        $quoteMock = $this
            ->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getShippingAddress',
                'getBillingAddress',
                'isVirtual',
                'getExtensionAttributes',
                'removeAddress'
            ])
            ->getMock();
        $cartExtensionAttributes = $this
            ->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote', 'setShippingAssignments'])
            ->getMockForAbstractClass();
        $quoteMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($cartExtensionAttributes);
        $cartExtensionAttributes->expects($this->any())
            ->method('getNegotiableQuote')
            ->willReturn($negotiableQuoteMock);

        $negotiableQuoteMock->expects($this->any())
            ->method('getIsAddressDraft')
            ->willReturn(true);
        $negotiableQuoteMock->expects($this->any())
            ->method('getStatus')
            ->willReturn('dummy_status');
        $shippingAddressMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote\Address::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'importCustomerAddressData',
                'setCollectShippingRates',
                'save',
                'delete'
            ])
            ->getMock();
        $shippingAddressMock->expects($this->any())
            ->method('importCustomerAddressData')
            ->willReturnSelf();
        $quoteMock->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($shippingAddressMock);
        $quoteMock->expects($this->any())
            ->method('getBillingAddress')
            ->willReturn($shippingAddressMock);

        return $quoteMock;
    }

    /**
     * Test for updateQuoteShippingAddressDraft() method.
     *
     * @param bool $isAddressDraft
     * @param string $snapshot
     * @return void
     * @dataProvider dataProviderUpdateQuoteShippingAddressDraft
     */
    public function testUpdateQuoteShippingAddressDraft($isAddressDraft, $snapshot)
    {
        $quoteMock = $this->buildNegotiableQuoteMock($isAddressDraft, $snapshot);
        $this->negotiableQuoteManagementMock->expects($this->any())
            ->method('getNegotiableQuote')
            ->willReturn($quoteMock);
        $this->restrictionMock->expects($this->any())->method('canProceedToCheckout')->willReturn(true);

        $this->createInstance();
        $this->address->updateQuoteShippingAddressDraft(1);
    }

    /**
     * Test for updateAddress() method.
     *
     * @param bool $isTaxChanged
     * @dataProvider dataProviderUpdateAddress
     * @return void
     */
    public function testUpdateAddress($isTaxChanged)
    {
        $quoteMock = $this->buildNegotiableQuoteMock();
        $this->quoteRepositoryMock->expects($this->once())
            ->method('get')
            ->willReturn($quoteMock);
        $taxDataMock = $this
            ->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteHistoryMock->expects($this->any())
            ->method('collectTaxDataFromQuote')
            ->willReturn($taxDataMock);
        $this->quoteHistoryMock->expects($this->any())
            ->method('collectOldDataFromQuote')
            ->willReturn($taxDataMock);
        $resultTaxDataMock = $this
            ->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getIsTaxChanged',
                'getIsShippingTaxChanged'
            ])
            ->getMock();
        $resultTaxDataMock->expects($this->once())
            ->method('getIsTaxChanged')
            ->willReturn($isTaxChanged);
        $resultTaxDataMock->expects($this->any())
            ->method('getIsShippingTaxChanged')
            ->willReturn($isTaxChanged);
        $this->quoteHistoryMock->expects($this->any())
            ->method('checkTaxes')
            ->willReturn($resultTaxDataMock);

        $this->createInstance();
        $this->address->updateAddress(1, 1);
    }

    /**
     * testUpdateQuoteShippingAddress data Provider.
     *
     * @return array
     */
    public function dataProviderUpdateQuoteShippingAddress()
    {
        return [
            [false],
            [true]
        ];
    }

    /**
     * testUpdateAddress data Provider.
     *
     * @return array
     */
    public function dataProviderUpdateAddress()
    {
        return [
            [false],
            [true]
        ];
    }

    /**
     * DataProvider updateQuoteShippingAddressDraft.
     *
     * @return array
     */
    public function dataProviderUpdateQuoteShippingAddressDraft()
    {
        return [
            [false, '[]'],
            [true, '{"shipping_address":{"address_id":"shipping"},"billing_address":{"address_id":"billing"}}'],
            [true, '{"shipping_address":{"address_id":"shipping"},"billing_address":[]}']
        ];
    }
}
