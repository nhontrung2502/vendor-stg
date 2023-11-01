<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Plugin\Customer\Controller\Address;

use Magento\Customer\Controller\Address\FormPost;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Plugin\Customer\Controller\Address\FormPostPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FormPostPluginTest extends TestCase
{
    /**
     * @var FormPostPlugin
     */
    protected $plugin;

    /**
     * @var Redirect|MockObject
     */
    protected $redirectMock;

    /**
     * @var ResultFactory|MockObject
     */
    protected $resultFactoryMock;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->resultFactoryMock =
            $this->createPartialMock(ResultFactory::class, ['create']);
        $this->redirectMock = $this->createMock(Redirect::class);

        $objectManager = new ObjectManager($this);
        $this->plugin = $objectManager->getObject(
            FormPostPlugin::class,
            ['resultFactory' => $this->resultFactoryMock]
        );
    }

    /**
     * Test for afterExecute method
     *
     * @param mixed $quoteId
     * @dataProvider afterExecuteDataProvider
     */
    public function testAfterExecute($quoteId)
    {
        if ($quoteId) {
            $this->resultFactoryMock->expects($this->any())
                ->method('create')
                ->with(ResultFactory::TYPE_REDIRECT)
                ->willReturn($this->redirectMock);
            $this->redirectMock->expects($this->once())
                ->method('setPath')
                ->with(
                    'negotiable_quote/quote/view',
                    ['quote_id' => $quoteId]
                )->willReturnSelf();
        }

        $subjectMock =
            $this->createPartialMock(FormPost::class, ['getRequest']);
        $requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $requestMock->expects($this->once())
            ->method('getParam')
            ->with('quoteId')
            ->willReturn($quoteId);
        $subjectMock->expects($this->once())
            ->method('getRequest')
            ->willReturn($requestMock);
        $result = $this->plugin->afterExecute($subjectMock, $this->redirectMock);

        $this->assertEquals($this->redirectMock, $result);
    }

    /**
     * Data Provider for testAfterExecute
     *
     * @return array
     */
    public function afterExecuteDataProvider()
    {
        return [
            [1],
            [null],
        ];
    }
}
