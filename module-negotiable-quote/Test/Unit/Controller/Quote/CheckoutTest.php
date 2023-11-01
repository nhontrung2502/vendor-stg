<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Quote;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface;
use Magento\NegotiableQuote\Controller\Quote\Checkout;
use Magento\NegotiableQuote\Model\CheckoutQuoteValidator;
use Magento\NegotiableQuote\Model\NegotiableQuote;
use Magento\NegotiableQuote\Model\Restriction\Customer;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CheckoutTest extends TestCase
{
    /**
     * @var Checkout
     */
    private $controller;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var JsonFactory|MockObject
     */
    private $resultJsonFactory;

    /**
     * @var CheckoutQuoteValidator|MockObject
     */
    private $checkoutQuoteValidator;

    /**
     * @var CartInterface|MockObject
     */
    private $quote;

    /**
     * @var RequestInterface|MockObject
     */
    private $resourse;

    /**
     * @var Customer|MockObject
     */
    private $customerRestriction;

    /**
     * @var NegotiableQuoteItemManagementInterface
     */
    private $quoteItemManagement;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->resourse = $this->getMockForAbstractClass(RequestInterface::class);
        $this->messageManager = $this->getMockForAbstractClass(ManagerInterface::class);
        $this->quoteItemManagement =
            $this->getMockForAbstractClass(NegotiableQuoteItemManagementInterface::class);
        $redirectFactory =
            $this->createPartialMock(RedirectFactory::class, ['create']);
        $redirect = $this->createMock(Redirect::class);
        $redirect->expects($this->any())->method('setPath')->willReturnSelf();
        $redirectFactory->expects($this->any())->method('create')->willReturn($redirect);
        $this->resultJsonFactory =
            $this->createPartialMock(JsonFactory::class, ['create']);
        $this->quoteRepository = $this->getMockForAbstractClass(CartRepositoryInterface::class);
        $this->customerRestriction = $this->createMock(Customer::class);
        $this->quote = $this->getMockForAbstractClass(
            CartInterface::class,
            [],
            '',
            false,
            true,
            true,
            []
        );
        $this->quoteRepository->expects($this->atLeastOnce())->method('get')->willReturn($this->quote);
        $this->checkoutQuoteValidator = $this->createMock(CheckoutQuoteValidator::class);

        $objectManager = new ObjectManager($this);
        $this->controller = $objectManager->getObject(
            Checkout::class,
            [
                'resultJsonFactory' => $this->resultJsonFactory,
                'quoteRepository' => $this->quoteRepository,
                'customerRestriction' => $this->customerRestriction,
                'checkoutQuoteValidator' => $this->checkoutQuoteValidator,
                '_request' => $this->resourse,
                'messageManager' => $this->messageManager,
                'resultRedirectFactory' => $redirectFactory,
                'quoteItemManagement' => $this->quoteItemManagement,
            ]
        );
    }

    /**
     * Test of execute() method.
     *
     * @param int $invalidItemsQty
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecute($invalidItemsQty)
    {
        $this->resourse->expects($this->once())
            ->method('getParam')->with('quote_id')->willReturn(1);
        $this->checkoutQuoteValidator->expects($this->once())
            ->method('countInvalidQtyItems')
            ->with($this->quote)
            ->willReturn($invalidItemsQty);
        $this->customerRestriction->expects($this->once())->method('canSubmit')->willReturn(true);
        $quoteNegotiation = $this->createMock(NegotiableQuote::class);
        $extensionAttributes = $this->getMockForAbstractClass(
            CartExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getNegotiableQuote', 'setShippingAssignments']
        );
        $extensionAttributes->expects($this->any())->method('getNegotiableQuote')
            ->willReturn($quoteNegotiation);
        $this->quote->expects($this->any())->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        $quoteNegotiation->expects($this->once())->method('getNegotiatedPriceValue')->willReturn(null);
        $this->quoteItemManagement->expects($this->once())->method('recalculateOriginalPriceTax')->with(1);
        if ($invalidItemsQty > 0) {
            $message = __(
                '%1 products require your attention. Please contact the Seller if you have any questions.',
                $invalidItemsQty
            );
            $this->messageManager->expects($this->once())
                ->method('addError')
                ->with($message);
        }
        $result = $this->controller->execute();
        $this->assertInstanceOf(HttpGetActionInterface::class, $this->controller);
        $this->assertInstanceOf(Redirect::class, $result);
    }

    /**
     * Data Provider for testExecute().
     *
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [0],
            [1]
        ];
    }
}
