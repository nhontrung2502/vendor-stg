<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Adminhtml\Quote;

use Magento\Backend\Model\Session\Quote;
use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Helper\Product\Composite;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Layout\ProcessorInterface;
use Magento\Framework\View\Result\Layout;
use Magento\NegotiableQuote\Api\NegotiableQuoteManagementInterface;
use Magento\NegotiableQuote\Controller\Adminhtml\Quote\ConfigureProductToAdd;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Configure Product To Add.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigureProductToAddTest extends TestCase
{
    /**
     * @var ConfigureProductToAdd
     */
    private $controller;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var DataObject|MockObject
     */
    private $dataObject;

    /**
     * @var Quote|MockObject
     */
    private $sessionQuote;

    /**
     * @var Composite|MockObject
     */
    private $compositeHelper;

    /**
     * @var NegotiableQuoteManagementInterface|MockObject
     */
    private $quoteManagement;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->dataObject = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['setOk', 'setProductId', 'setCurrentStoreId', 'setCurrentCustomerId', 'setBuyRequest'])
            ->getMock();
        $this->sessionQuote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->compositeHelper = $this->getMockBuilder(Composite::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteManagement = $this
            ->getMockBuilder(NegotiableQuoteManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    /**
     * Creates an instance of subject under test.
     *
     * @return void
     */
    private function createInstance()
    {
        $objectManager = new ObjectManager($this);
        $this->controller = $objectManager->getObject(
            ConfigureProductToAdd::class,
            [
                'request' => $this->request,
                'dataObject' => $this->dataObject,
                'sessionQuote' => $this->sessionQuote,
                'compositeHelper' => $this->compositeHelper,
                'quoteManagement' => $this->quoteManagement,
                'productTypesToReplace' => [Type::TYPE_CODE]
            ]
        );
    }

    /**
     * Test execute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $productId = '1';
        $quoteId = 1;
        $this->request->expects(($this->atLeastOnce()))
            ->method('getParam')
            ->withConsecutive(
                ['id'],
                ['quote_id'],
                ['config']
            )
            ->willReturnOnConsecutiveCalls(
                $productId,
                $quoteId,
                'testConfig'
            );
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customer->expects($this->once())->method('getId')->willReturn(1);
        $quote = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $quote->expects($this->once())->method('getCustomer')->willReturn($customer);
        $this->quoteManagement->expects($this->once())->method('getNegotiableQuote')->willReturn($quote);
        $this->dataObject->expects($this->once())
            ->method('setOk');
        $store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $store->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $this->sessionQuote->expects($this->once())
            ->method('getStore')
            ->willReturn($store);
        $this->dataObject->expects($this->once())
            ->method('setBuyRequest');
        $resultLayout = $this->getMockBuilder(Layout::class)
            ->disableOriginalConstructor()
            ->getMock();
        $layoutMock = $this->getLayoutMock();
        $resultLayout->expects($this->once())->method('getLayout')->willReturn($layoutMock);
        $this->compositeHelper->expects($this->once())
            ->method('renderConfigureResult')
            ->willReturn($resultLayout);

        $this->createInstance();
        $result = $this->controller->execute();

        $this->assertSame($result, $resultLayout);
    }

    /**
     * Get Layout Mock.
     *
     * @return \Magento\Framework\View\Layout|MockObject
     */
    private function getLayoutMock()
    {
        $customProductTypeHandle = 'catalog_product_view_type_' . Type::TYPE_CODE;
        $updateLayoutMock = $this->getMockBuilder(ProcessorInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getHandles', 'addHandle', 'removeHandle'])
            ->getMockForAbstractClass();
        $origHandles = [
            'CATALOG_PRODUCT_COMPOSITE_CONFIGURE',
            $customProductTypeHandle
        ];
        $updateLayoutMock->expects($this->once())->method('getHandles')->willReturn($origHandles);
        $updateLayoutMock->expects($this->atLeastOnce())->method('removeHandle')
            ->withConsecutive(
                ['CATALOG_PRODUCT_COMPOSITE_CONFIGURE'],
                [$customProductTypeHandle]
            )
            ->willReturnSelf();
        $updateLayoutMock->expects($this->atLeastOnce())->method('addHandle')
            ->withConsecutive(
                ['negotiable_quote_catalog_product_composite_configure'],
                ['negotiablequote_' . $customProductTypeHandle]
            )
            ->willReturnSelf();
        $layoutMock = $this->getMockBuilder(\Magento\Framework\View\Layout::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUpdate'])
            ->getMock();
        $layoutMock->expects($this->once())->method('getUpdate')->willReturn($updateLayoutMock);

        return $layoutMock;
    }
}
