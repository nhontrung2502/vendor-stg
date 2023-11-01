<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Plugin\Checkout\Controller\Index;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Checkout\Controller\Index\Index;
use Magento\Company\Model\CompanyUserPermission;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\NegotiableQuote\Plugin\Checkout\Controller\Index\IndexPlugin;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for IndexPlugin.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IndexPluginTest extends TestCase
{
    /**
     * @var RedirectFactory|MockObject
     */
    private $resultRedirectFactory;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var RestrictionInterface|MockObject
     */
    private $restriction;

    /**
     * @var CompanyUserPermission|MockObject
     */
    private $companyUserPermission;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var UserContextInterface|MockObject
     */
    private $customerContext;

    /**
     * @var IndexPlugin
     */
    private $indexPlugin;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resultRedirectFactory = $this
            ->getMockBuilder(RedirectFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->restriction = $this
            ->getMockBuilder(RestrictionInterface::class)
            ->setMethods(['canProceedToCheckout'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyUserPermission = $this->getMockBuilder(CompanyUserPermission::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerContext = $this->getMockBuilder(UserContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $objectManager = new ObjectManager($this);
        $this->indexPlugin = $objectManager->getObject(
            IndexPlugin::class,
            [
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'quoteRepository' => $this->quoteRepository,
                'restriction' => $this->restriction,
                'companyUserPermission' => $this->companyUserPermission,
                'customerContext' => $this->customerContext,
                'request' => $this->request
            ]
        );
    }

    /**
     * Test aroundExecute.
     *
     * @param bool $isCurrentUserCompanyUser
     * @param int $quoteId
     * @param string $path
     * @return void
     * @dataProvider testAroundExecuteDataProvider
     */
    public function testAroundExecute($quoteId, $isCurrentUserCompanyUser, $path)
    {
        $this->request->expects($this->once())->method('getParam')->with('negotiableQuoteId')->willReturn($quoteId);
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quoteRepository->expects($this->once())->method('get')->with($quoteId)->willReturn($quote);
        $this->restriction->expects($this->once())->method('setQuote')->with($quote)->willReturnSelf();
        $this->restriction->expects($this->once())->method('canProceedToCheckout')->willReturn(false);
        $this->companyUserPermission->expects($this->once())->method('isCurrentUserCompanyUser')
            ->willReturn($isCurrentUserCompanyUser);
        $resultRedirect = $this->getMockForAbstractClass(
            ResultInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['setPath']
        );
        $resultRedirect->expects($this->once())->method('setPath')->with($path)->willReturnSelf();
        $this->resultRedirectFactory->expects($this->once())->method('create')->willReturn($resultRedirect);
        $subject = $this->getMockBuilder(Index::class)
            ->disableOriginalConstructor()
            ->getMock();
        $proceed = function ($object) {
            return $object;
        };
        $this->customerContext->expects($this->once())->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_CUSTOMER);
        $this->customerContext->expects($this->once())->method('getUserId')->willReturn(1);

        $this->assertInstanceOf(
            ResultInterface::class,
            $this->indexPlugin->aroundExecute($subject, $proceed)
        );
    }

    /**
     * Test aroundExecute with guest user.
     */
    public function testAroundExecuteForGuest()
    {
        $this->request->expects($this->once())->method('getParam')->with('negotiableQuoteId')->willReturn(1);
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quoteRepository->expects($this->once())->method('get')->with(1)->willReturn($quote);
        $this->restriction->expects($this->once())->method('setQuote')->with($quote)->willReturnSelf();
        $this->restriction->expects($this->once())->method('canProceedToCheckout')->willReturn(false);
        $this->companyUserPermission->expects($this->never())->method('isCurrentUserCompanyUser');
        $resultRedirect = $this->getMockForAbstractClass(
            ResultInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['setPath']
        );
        $resultRedirect->expects($this->once())->method('setPath')->with('customer/account/login')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->once())->method('create')->willReturn($resultRedirect);
        $subject = $this->getMockBuilder(Index::class)
            ->disableOriginalConstructor()
            ->getMock();
        $proceed = function ($object) {
            return $object;
        };
        $this->customerContext->expects($this->once())->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_GUEST);
        $this->customerContext->expects($this->once())->method('getUserId')->willReturn(1);

        $this->assertInstanceOf(
            ResultInterface::class,
            $this->indexPlugin->aroundExecute($subject, $proceed)
        );
    }

    /**
     * Test aroundExecute with NoSuchEntityException.
     *
     * @return void
     */
    public function testAroundExecuteWithNoSuchEntityException()
    {
        $this->request->expects($this->once())->method('getParam')->with('negotiableQuoteId')->willReturn(1);
        $exception = new NoSuchEntityException();
        $this->quoteRepository->expects($this->once())->method('get')->willThrowException($exception);
        $this->restriction->expects($this->once())->method('canProceedToCheckout')->willReturn(false);
        $subject = $this->getMockBuilder(Index::class)
            ->disableOriginalConstructor()
            ->getMock();
        $proceed = function () {
            return null;
        };

        $this->indexPlugin->aroundExecute($subject, $proceed);
    }

    /**
     * DataProvider testAroundExecute.
     *
     * @return array
     */
    public function testAroundExecuteDataProvider()
    {
        return [
            [1, true, 'company/accessdenied'],
            [1, false, 'noroute'],
        ];
    }
}
