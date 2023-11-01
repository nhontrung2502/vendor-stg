<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterfaceFactory;
use Magento\NegotiableQuote\Model\NegotiableQuoteRepository;
use Magento\NegotiableQuote\Model\Query\GetList;
use Magento\NegotiableQuote\Model\ResourceModel\NegotiableQuote;
use Magento\NegotiableQuote\Model\Validator\ValidatorInterface;
use Magento\NegotiableQuote\Model\Validator\ValidatorInterfaceFactory;
use Magento\NegotiableQuote\Model\Validator\ValidatorResult;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Magento\NegotiableQuote\Model\NegotiableQuoteRepository class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class NegotiableQuoteRepositoryTest extends TestCase
{
    /**
     * @var NegotiableQuoteInterfaceFactory|MockObject
     */
    private $negotiableQuoteFactory;

    /**
     * @var NegotiableQuote|MockObject
     */
    private $negotiableQuoteResource;

    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var GetList|MockObject
     */
    private $negotiableQuoteList;

    /**
     * @var ValidatorInterfaceFactory|MockObject
     */
    private $validatorFactory;

    /**
     * @var NegotiableQuoteRepository
     */
    private $repository;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->negotiableQuoteFactory = $this->getMockBuilder(
            NegotiableQuoteInterfaceFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->negotiableQuoteResource = $this->getMockBuilder(
            NegotiableQuote::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->userContext = $this->getMockBuilder(UserContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->negotiableQuoteList = $this->getMockBuilder(GetList::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validatorFactory = $this->getMockBuilder(
            ValidatorInterfaceFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->repository = $objectManager->getObject(
            NegotiableQuoteRepository::class,
            [
                'negotiableQuoteFactory' => $this->negotiableQuoteFactory,
                'negotiableQuoteResource' => $this->negotiableQuoteResource,
                'userContext' => $this->userContext,
                'negotiableQuoteList' => $this->negotiableQuoteList,
                'validatorFactory' => $this->validatorFactory,
            ]
        );
    }

    /**
     * Test getList method.
     *
     * @return void
     */
    public function testGetList()
    {
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $result = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->negotiableQuoteList->expects($this->once())
            ->method('getList')
            ->with($searchCriteria, false)
            ->willReturn($result);

        $this->assertSame($result, $this->repository->getList($searchCriteria));
    }

    /**
     * Test getListByCustomerId method.
     *
     * @return void
     */
    public function testGetListByCustomerId()
    {
        $customerId = 1;
        $item = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->negotiableQuoteList->expects($this->once())
            ->method('getListByCustomerId')
            ->with($customerId)
            ->willReturn([$item]);

        $this->assertSame([$item], $this->repository->getListByCustomerId($customerId));
    }

    /**
     * Test getById method.
     *
     * @return void
     */
    public function testGetById()
    {
        $quoteId = 1;
        $negotiableQuote = $this->getMockBuilder(
            NegotiableQuoteInterface::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['load'])
            ->getMockForAbstractClass();
        $this->negotiableQuoteFactory->expects($this->once())->method('create')->willReturn($negotiableQuote);
        $negotiableQuote->expects($this->once())->method('load')->with($quoteId)->willReturnSelf();

        $this->assertSame($negotiableQuote, $this->repository->getById($quoteId));
    }

    /**
     * Test save method.
     *
     * @return void
     */
    public function testSave()
    {
        $quoteId = 1;
        $negotiableQuote = $this->getMockBuilder(
            NegotiableQuoteInterface::class
        )
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'hasData',
                    'getNegotiatedPriceValue',
                    'getCreatorId',
                    'setCreatorId',
                    'setCreatorType',
                    'getQuoteId',
                    'getStatus',
                    'setStatus'
                ]
            )
            ->getMockForAbstractClass();
        $oldQuote = $this->getMockBuilder(
            \Magento\NegotiableQuote\Model\NegotiableQuote::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiatedPriceValue', 'getCreatorId', 'getStatus', 'getQuoteId'])
            ->getMock();
        $validator = $this->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $validationResult = $this->getMockBuilder(ValidatorResult::class)
            ->disableOriginalConstructor()
            ->getMock();
        $negotiableQuote->expects($this->atLeastOnce())->method('getQuoteId')->willReturn($quoteId);
        $this->negotiableQuoteFactory->expects($this->once())->method('create')->willReturn($oldQuote);
        $this->negotiableQuoteResource->expects($this->once())
            ->method('load')
            ->with($oldQuote, $quoteId)
            ->willReturnSelf();
        $negotiableQuote->expects($this->atLeastOnce())
            ->method('hasData')
            ->withConsecutive(
                [NegotiableQuoteInterface::NEGOTIATED_PRICE_VALUE],
                [NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE],
                [NegotiableQuoteInterface::NEGOTIATED_PRICE_VALUE],
                [NegotiableQuoteInterface::SHIPPING_PRICE],
                [NegotiableQuoteInterface::EXPIRATION_PERIOD]
            )
            ->willReturnOnConsecutiveCalls(true, false, false, false, false);
        $oldQuote->expects($this->never())->method('getNegotiatedPriceValue');
        $negotiableQuote->expects($this->once())->method('getNegotiatedPriceValue')->willReturn(100);
        $negotiableQuote->expects($this->never())->method('setNegotiatedPriceType')->with(null)->willReturnSelf();
        $negotiableQuote->expects($this->once())->method('getCreatorId')->willReturn(null);
        $oldQuote->expects($this->once())->method('getCreatorId')->willReturn(null);
        $oldQuote->expects($this->once())->method('getQuoteId')->willReturn($quoteId);
        $this->userContext->expects($this->once())->method('getUserId')->willReturn(1);
        $this->userContext->expects($this->atLeastOnce())
            ->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_ADMIN);
        $negotiableQuote->expects($this->once())->method('setCreatorId')->with(1)->willReturnSelf();
        $negotiableQuote->expects($this->once())->method('setCreatorType')->with(2)->willReturnSelf();
        $negotiableQuote->expects($this->once())->method('getStatus')->willReturn(null);
        $oldQuote->expects($this->once())->method('getStatus')->willReturn(NegotiableQuoteInterface::STATUS_CREATED);
        $negotiableQuote->expects($this->never())
            ->method('setStatus')
            ->with(NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN)
            ->willReturnSelf();
        $this->validatorFactory->expects($this->once())
            ->method('create')
            ->with(['action' => 'save'])
            ->willReturn($validator);
        $validator->expects($this->once())
            ->method('validate')
            ->with(['negotiableQuote' => $negotiableQuote])
            ->willReturn($validationResult);
        $validationResult->expects($this->once())->method('hasMessages')->willReturn(false);
        $this->negotiableQuoteResource->expects($this->once())
            ->method('saveNegotiatedQuoteData')
            ->with($negotiableQuote)
            ->willReturnSelf();

        $this->assertTrue($this->repository->save($negotiableQuote));
    }

    /**
     * Test save method with empty negotiated price.
     *
     * @return void
     */
    public function testSaveWithEmptyNegotiatedPriceType()
    {
        $quoteId = 1;
        $negotiableQuote = $this->getMockBuilder(
            NegotiableQuoteInterface::class
        )
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'hasData',
                    'getNegotiatedPriceValue',
                    'getCreatorId',
                    'setCreatorId',
                    'setCreatorType',
                    'getQuoteId',
                    'getStatus',
                    'setStatus',
                    'getData'
                ]
            )
            ->getMockForAbstractClass();
        $oldQuote = $this->getMockBuilder(
            \Magento\NegotiableQuote\Model\NegotiableQuote::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiatedPriceValue', 'getCreatorId', 'getStatus', 'getQuoteId', 'getData'])
            ->getMock();
        $validator = $this->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $validationResult = $this->getMockBuilder(ValidatorResult::class)
            ->disableOriginalConstructor()
            ->getMock();
        $negotiableQuote->expects($this->atLeastOnce())->method('getQuoteId')->willReturn($quoteId);
        $this->negotiableQuoteFactory->expects($this->once())->method('create')->willReturn($oldQuote);
        $this->negotiableQuoteResource->expects($this->once())
            ->method('load')
            ->with($oldQuote, $quoteId)
            ->willReturnSelf();
        $negotiableQuote->expects($this->atLeastOnce())
            ->method('hasData')
            ->withConsecutive(
                [NegotiableQuoteInterface::NEGOTIATED_PRICE_VALUE],
                [NegotiableQuoteInterface::NEGOTIATED_PRICE_TYPE],
                [NegotiableQuoteInterface::NEGOTIATED_PRICE_VALUE],
                [NegotiableQuoteInterface::SHIPPING_PRICE],
                [NegotiableQuoteInterface::EXPIRATION_PERIOD]
            )
            ->willReturnOnConsecutiveCalls(false, false, false, false, true);
        $negotiableQuote->expects($this->atLeastOnce())
            ->method('getData')
            ->with(NegotiableQuoteInterface::EXPIRATION_PERIOD)
            ->willReturn('20120415');
        $oldQuote->expects($this->atLeastOnce())
            ->method('getData')
            ->with(NegotiableQuoteInterface::EXPIRATION_PERIOD)
            ->willReturn('20120417');
        $oldQuote->expects($this->once())->method('getNegotiatedPriceValue')->willReturn(null);
        $negotiableQuote->expects($this->never())->method('getNegotiatedPriceValue')->willReturn(100);
        $negotiableQuote->expects($this->once())->method('setNegotiatedPriceType')->with(null)->willReturnSelf();
        $negotiableQuote->expects($this->once())->method('getCreatorId')->willReturn(null);
        $oldQuote->expects($this->once())->method('getCreatorId')->willReturn(null);
        $oldQuote->expects($this->atLeastOnce())->method('getQuoteId')->willReturn($quoteId);
        $this->userContext->expects($this->once())->method('getUserId')->willReturn(1);
        $this->userContext->expects($this->atLeastOnce())
            ->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_ADMIN);
        $negotiableQuote->expects($this->once())->method('setCreatorId')->with(1)->willReturnSelf();
        $negotiableQuote->expects($this->once())->method('setCreatorType')->with(2)->willReturnSelf();
        $negotiableQuote->expects($this->once())->method('getStatus')->willReturn(null);
        $oldQuote->expects($this->once())->method('getStatus')->willReturn(NegotiableQuoteInterface::STATUS_CREATED);
        $negotiableQuote->expects($this->once())
            ->method('setStatus')
            ->with(NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN)
            ->willReturnSelf();
        $this->validatorFactory->expects($this->once())
            ->method('create')
            ->with(['action' => 'save'])
            ->willReturn($validator);
        $validator->expects($this->once())
            ->method('validate')
            ->with(['negotiableQuote' => $negotiableQuote])
            ->willReturn($validationResult);
        $validationResult->expects($this->once())->method('hasMessages')->willReturn(false);
        $this->negotiableQuoteResource->expects($this->once())
            ->method('saveNegotiatedQuoteData')
            ->with($negotiableQuote)
            ->willReturnSelf();

        $this->assertTrue($this->repository->save($negotiableQuote));
    }

    /**
     * Test save method without quote id.
     *
     * @return void
     */
    public function testSaveWithoutQuote()
    {
        $quoteId = 0;
        $negotiableQuote = $this->getMockBuilder(
            NegotiableQuoteInterface::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['getQuoteId'])
            ->getMockForAbstractClass();
        $negotiableQuote->expects($this->atLeastOnce())->method('getQuoteId')->willReturn($quoteId);

        $this->assertFalse($this->repository->save($negotiableQuote));
    }

    /**
     * Test save method with exception.
     *
     * @return void
     */
    public function testSaveWithException()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotSaveException');
        $this->expectExceptionMessage('Changes to the negotiated quote were not saved. Please try again.');
        $exception = new \Exception();
        $quoteId = 1;
        $negotiableQuote = $this->getMockBuilder(
            NegotiableQuoteInterface::class
        )
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'hasData',
                    'getNegotiatedPriceValue',
                    'getCreatorId',
                    'setCreatorId',
                    'setCreatorType',
                    'getQuoteId',
                    'getStatus',
                    'setStatus'
                ]
            )
            ->getMockForAbstractClass();
        $oldQuote = $this->getMockBuilder(
            \Magento\NegotiableQuote\Model\NegotiableQuote::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiatedPriceValue', 'getCreatorId', 'getStatus'])
            ->getMock();
        $validator = $this->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $validationResult = $this->getMockBuilder(ValidatorResult::class)
            ->disableOriginalConstructor()
            ->getMock();
        $negotiableQuote->expects($this->atLeastOnce())->method('getQuoteId')->willReturn($quoteId);
        $this->negotiableQuoteFactory->expects($this->once())->method('create')->willReturn($oldQuote);
        $this->negotiableQuoteResource->expects($this->once())
            ->method('load')
            ->with($oldQuote, $quoteId)
            ->willReturnSelf();
        $negotiableQuote->expects($this->once())
            ->method('hasData')
            ->with(NegotiableQuoteInterface::NEGOTIATED_PRICE_VALUE)
            ->willReturn(true);
        $oldQuote->expects($this->never())->method('getNegotiatedPriceValue');
        $negotiableQuote->expects($this->once())->method('getNegotiatedPriceValue')->willReturn(100);
        $negotiableQuote->expects($this->never())->method('setNegotiatedPriceType')->with(null)->willReturnSelf();
        $negotiableQuote->expects($this->once())->method('getCreatorId')->willReturn(null);
        $oldQuote->expects($this->once())->method('getCreatorId')->willReturn(null);
        $this->userContext->expects($this->once())->method('getUserId')->willReturn(1);
        $this->userContext->expects($this->atLeastOnce())
            ->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_ADMIN);
        $negotiableQuote->expects($this->once())->method('setCreatorId')->with(1)->willReturnSelf();
        $negotiableQuote->expects($this->once())->method('setCreatorType')->with(2)->willReturnSelf();
        $negotiableQuote->expects($this->never())
            ->method('setStatus')
            ->with(NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN)
            ->willReturnSelf();
        $this->validatorFactory->expects($this->once())
            ->method('create')
            ->with(['action' => 'save'])
            ->willReturn($validator);
        $validator->expects($this->once())
            ->method('validate')
            ->with(['negotiableQuote' => $negotiableQuote])
            ->willReturn($validationResult);
        $validationResult->expects($this->once())->method('hasMessages')->willReturn(false);
        $this->negotiableQuoteResource->expects($this->once())
            ->method('saveNegotiatedQuoteData')
            ->with($negotiableQuote)
            ->willThrowException($exception);

        $this->repository->save($negotiableQuote);
    }

    /**
     * Test save with InputException.
     *
     * @return void
     */
    public function testSaveWithInputException()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage('You cannot update the quote status.');
        $quoteId = 1;
        $negotiableQuote = $this->getMockBuilder(
            NegotiableQuoteInterface::class
        )
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'hasData',
                    'getNegotiatedPriceValue',
                    'getCreatorId',
                    'setCreatorId',
                    'setCreatorType',
                    'getQuoteId',
                    'getStatus',
                    'setStatus'
                ]
            )
            ->getMockForAbstractClass();
        $message = __('You cannot update the quote status.');
        $oldQuote = $this->getMockBuilder(
            \Magento\NegotiableQuote\Model\NegotiableQuote::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiatedPriceValue', 'getCreatorId', 'getStatus'])
            ->getMock();
        $validator = $this->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $validationResult = $this->getMockBuilder(ValidatorResult::class)
            ->disableOriginalConstructor()
            ->getMock();
        $negotiableQuote->expects($this->atLeastOnce())->method('getQuoteId')->willReturn($quoteId);
        $this->negotiableQuoteFactory->expects($this->once())->method('create')->willReturn($oldQuote);
        $this->negotiableQuoteResource->expects($this->once())
            ->method('load')
            ->with($oldQuote, $quoteId)
            ->willReturnSelf();
        $negotiableQuote->expects($this->once())
            ->method('hasData')
            ->with(NegotiableQuoteInterface::NEGOTIATED_PRICE_VALUE)
            ->willReturnOnConsecutiveCalls(true);
        $oldQuote->expects($this->never())->method('getNegotiatedPriceValue');
        $negotiableQuote->expects($this->once())->method('getNegotiatedPriceValue')->willReturn(100);
        $negotiableQuote->expects($this->never())->method('setNegotiatedPriceType')->with(null)->willReturnSelf();
        $negotiableQuote->expects($this->once())->method('getCreatorId')->willReturn(null);
        $this->userContext->expects($this->once())->method('getUserId')->willReturn(1);
        $this->userContext->expects($this->atLeastOnce())
            ->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_ADMIN);
        $negotiableQuote->expects($this->once())->method('setCreatorId')->with(1)->willReturnSelf();
        $negotiableQuote->expects($this->once())->method('setCreatorType')->with(2)->willReturnSelf();
        $negotiableQuote->expects($this->never())
            ->method('setStatus')
            ->with(NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN)
            ->willReturnSelf();
        $this->validatorFactory->expects($this->once())
            ->method('create')
            ->with(['action' => 'save'])
            ->willReturn($validator);
        $validator->expects($this->once())
            ->method('validate')
            ->with(['negotiableQuote' => $negotiableQuote])
            ->willReturn($validationResult);
        $validationResult->expects($this->once())->method('hasMessages')->willReturn(true);
        $validationResult->expects($this->once())->method('getMessages')->willReturn([$message]);

        $this->repository->save($negotiableQuote);
    }

    /**
     * Test delete method.
     *
     * @return void
     */
    public function testDelete()
    {
        $negotiableQuote = $this->getMockBuilder(\Magento\NegotiableQuote\Model\NegotiableQuote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->negotiableQuoteResource->expects($this->once())
            ->method('delete')
            ->with($negotiableQuote)
            ->willReturnSelf();

        $this->repository->delete($negotiableQuote);
    }

    /**
     * Test delete method with exception.
     *
     * @return void
     */
    public function testDeleteWithException()
    {
        $this->expectException('Magento\Framework\Exception\StateException');
        $this->expectExceptionMessage('Cannot delete negotiable quote');
        $negotiableQuote = $this->getMockBuilder(\Magento\NegotiableQuote\Model\NegotiableQuote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->negotiableQuoteResource->expects($this->once())
            ->method('delete')
            ->with($negotiableQuote)
            ->willThrowException(new \Exception());

        $this->repository->delete($negotiableQuote);
    }
}
