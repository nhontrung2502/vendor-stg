<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Customer;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AddressSearchResultsInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Block\Address\Renderer\RendererInterface;
use Magento\Customer\Model\Address\Config;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\NegotiableQuote\Model\Customer\AddressProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AddressProviderTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var AddressProvider|MockObject
     */
    private $addressProvider;

    /**
     * @var AddressRepositoryInterface|MockObject
     */
    private $addressRepository;

    /**
     * @var Config|MockObject
     */
    private $addressConfig;

    /**
     * @var FilterBuilder|MockObject
     */
    private $filterBuilder;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var ExtensibleDataObjectConverter|MockObject
     */
    private $extensibleDataObjectConverter;

    /**
     * @var CustomerInterface|MockObject
     */
    private $customer;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->addressRepository = $this->getMockBuilder(AddressRepositoryInterface::class)
            ->setMethods([
                'getList',
                'getById'
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->addressConfig = $this->getMockBuilder(Config::class)
            ->setMethods(['getFormatByCode'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->filterBuilder = $this->getMockBuilder(FilterBuilder::class)
            ->setMethods([
                'setField',
                'setConditionType',
                'setValue',
                'create'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->setMethods([
                'addFilters',
                'create'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->extensibleDataObjectConverter = $this
            ->getMockBuilder(ExtensibleDataObjectConverter::class)
            ->setMethods(['toFlatArray'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->customer = $this->getMockBuilder(CustomerInterface::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->addressProvider = $this->objectManagerHelper->getObject(
            AddressProvider::class,
            [
                'addressRepository' => $this->addressRepository,
                'addressConfig' => $this->addressConfig,
                'filterBuilder' => $this->filterBuilder,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'extensibleDataObjectConverter' => $this->extensibleDataObjectConverter,
                'customer' => $this->customer
            ]
        );
    }

    /**
     * Test getAllCustomerAddresses method.
     *
     * @return void
     */
    public function testGetAllCustomerAddresses()
    {
        $address = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $addresses = [$address];

        $customerId = 35;
        $this->customer->expects($this->once())->method('getId')->willReturn($customerId);

        $this->filterBuilder->expects($this->exactly(1))->method('setField')->willReturnSelf();
        $this->filterBuilder->expects($this->exactly(1))->method('setConditionType')->willReturnSelf();
        $this->filterBuilder->expects($this->exactly(1))->method('setValue')->willReturnSelf();
        $this->filterBuilder->expects($this->exactly(1))->method('create')->willReturnSelf();

        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaBuilder->expects($this->exactly(1))->method('addFilters')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->exactly(1))->method('create')->willReturn($searchCriteria);

        $searchList = $this->getMockBuilder(AddressSearchResultsInterface::class)
            ->setMethods(['getItems'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchList->expects($this->exactly(1))->method('getItems')->willReturn($addresses);

        $this->addressRepository->expects($this->exactly(1))->method('getList')->willReturn($searchList);

        $this->assertEquals($addresses, $this->addressProvider->getAllCustomerAddresses());
    }

    /**
     * Test getAllCustomerAddresses method with Exception.
     *
     * @return void
     */
    public function testGetAllCustomerAddressesWithException()
    {
        $addresses = [];

        $phrase = new Phrase('message');
        $exception = new LocalizedException($phrase);
        $this->customer->expects($this->once())->method('getId')->willThrowException($exception);

        $this->assertEquals($addresses, $this->addressProvider->getAllCustomerAddresses());
    }

    /**
     * Test getRenderedAddress method.
     *
     * @return void
     */
    public function testGetRenderedAddress()
    {
        $resultAddress = 'City, California, 12323';

        $address = $this->getMockBuilder(\Magento\Quote\Api\Data\AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $addressData = [
            \Magento\Quote\Api\Data\AddressInterface::KEY_POSTCODE => '25235'
        ];
        $this->extensibleDataObjectConverter->expects($this->exactly(1))->method('toFlatArray')
            ->with($address, [], \Magento\Quote\Api\Data\AddressInterface::class)
            ->willReturn($addressData);
        $this->setUpRendererMock($resultAddress);

        $this->assertEquals($resultAddress, $this->addressProvider->getRenderedAddress($address));
    }

    /**
     * Test getRenderedLineAddress method.
     *
     * @return void
     */
    public function testGetRenderedLineAddress()
    {
        $addressId = 64;
        $resultAddress = 'City, California, 12323';

        $street = ['City', 'California', '12323', 'City, California, 12323'];
        $address = $this->getMockBuilder(AddressInterface::class)
            ->setMethods(['getStreet'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $address->expects($this->exactly(1))->method('getStreet')->willReturn($street);

        $this->addressRepository->expects($this->exactly(1))->method('getById')->willReturn($address);

        $addressData = [1, 2, 3];
        $this->extensibleDataObjectConverter->expects($this->exactly(1))->method('toFlatArray')
            ->with($address, [], AddressInterface::class)
            ->willReturn($addressData);
        $this->setUpRendererMock($resultAddress);

        $this->assertEquals($resultAddress, $this->addressProvider->getRenderedLineAddress($addressId));
    }

    /**
     * Set up Renderer Mock.
     *
     * @param string $resultAddress
     * @return void
     */
    private function setUpRendererMock($resultAddress)
    {
        $renderer = $this->getMockBuilder(RendererInterface::class)
            ->setMethods(['renderArray'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $renderer->expects($this->exactly(1))->method('renderArray')->willReturn($resultAddress);

        $dataObject = $this->getMockBuilder(DataObject::class)
            ->setMethods(['getRenderer'])
            ->disableOriginalConstructor()
            ->getMock();
        $dataObject->expects($this->exactly(1))->method('getRenderer')->willReturn($renderer);
        $this->addressConfig->expects($this->exactly(1))->method('getFormatByCode')->willReturn($dataObject);
    }

    /**
     * Test getRenderedLineAddress method with Exception.
     *
     * @return void
     */
    public function testGetRenderedLineAddressWithException()
    {
        $addressId = 64;
        $resultAddress = '';

        $phrase = new Phrase('message');
        $exception = new LocalizedException($phrase);
        $this->addressRepository->expects($this->exactly(1))->method('getById')->willThrowException($exception);

        $this->assertEquals($resultAddress, $this->addressProvider->getRenderedLineAddress($addressId));
    }
}
