<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Controller\Quote;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Controller\Quote\Delete;
use Magento\NegotiableQuote\Model\Restriction\RestrictionInterface;
use Magento\NegotiableQuote\Model\SettingsProvider;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DeleteTest extends TestCase
{
    /**
     * @var Delete
     */
    private $controller;

    /**
     * @var RequestInterface|MockObject
     */
    private $resourse;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var NegotiableQuoteRepositoryInterface|MockObject
     */
    private $negotiableQuoteRepository;

    /**
     * @var Validator|MockObject
     */
    private $formKeyValidator;

    /**
     * @var SettingsProvider|MockObject
     */
    private $settingsProvider;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->resourse = $this->getMockForAbstractClass(RequestInterface::class);
        $this->messageManager = $this->getMockForAbstractClass(ManagerInterface::class);

        $redirectFactory =
            $this->createPartialMock(RedirectFactory::class, ['create']);
        $redirect = $this->createMock(Redirect::class);
        $redirect->expects($this->any())
            ->method('setPath')->willReturnSelf();
        $redirectFactory->expects($this->any())
            ->method('create')->willReturn($redirect);
        $this->quoteRepository = $this->getMockForAbstractClass(CartRepositoryInterface::class);
        $this->negotiableQuoteRepository =
            $this->getMockForAbstractClass(NegotiableQuoteRepositoryInterface::class);

        $customerRestriction =
            $this->getMockForAbstractClass(RestrictionInterface::class);
        $customerRestriction->expects($this->any())
            ->method('canDelete')->willReturn(true);
        $this->formKeyValidator =
            $this->createMock(Validator::class);
        $this->settingsProvider =
            $this->createPartialMock(SettingsProvider::class, ['getCurrentUserId']);

        $objectManager = new ObjectManager($this);
        $this->controller = $objectManager->getObject(
            Delete::class,
            [
                'quoteRepository' => $this->quoteRepository,
                'formKeyValidator' => $this->formKeyValidator,
                'negotiableQuoteRepository' => $this->negotiableQuoteRepository,
                'customerRestriction' => $customerRestriction,
                'messageManager' => $this->messageManager,
                'resultRedirectFactory' => $redirectFactory,
                '_request' => $this->resourse,
                'settingsProvider' => $this->settingsProvider
            ]
        );
    }

    /**
     * @dataProvider getQuoteIds
     *
     * @param int $quoteId
     * @param int $customerId
     * @param int $quoteCustomerId
     * @param \Exception $error
     * @param string $expectedResult
     */
    public function testExecute($quoteId, $customerId, $quoteCustomerId, $error, $expectedResult)
    {
        $this->formKeyValidator->expects($this->any())->method('validate')->willReturn(true);
        $this->resourse->expects($this->any())->method('getParam')->willReturn($quoteId);
        $quote = $this->createPartialMock(Quote::class, []);
        $quote->setCustomerId($quoteCustomerId);
        $this->quoteRepository->expects($this->any())
            ->method('get')->with($quoteId)->willReturn($quote);
        $this->settingsProvider->expects($this->any())->method('getCurrentUserId')->willReturn($customerId);
        $negotiableQuote = $this->getMockForAbstractClass(NegotiableQuoteInterface::class);

        if ($error) {
            $this->negotiableQuoteRepository->expects($this->any())
                ->method('getById')->willThrowException($error);
        } else {
            $this->negotiableQuoteRepository->expects($this->any())
                ->method('getById')->willReturn($negotiableQuote);
        }
        $result = '';
        $messageManager = $this->messageManager;
        $returnCallback = function ($data) use (&$result, $messageManager) {
            $result = $data;
            return $messageManager;
        };
        $returnExceptionCallback = function ($data, $text) use (&$result, $messageManager) {
            $result = $text;
            return $messageManager;
        };
        $this->messageManager->expects($this->any())
            ->method('addErrorMessage')->willReturnCallback($returnCallback);
        $this->messageManager->expects($this->any())
            ->method('addSuccessMessage')->willReturnCallback($returnCallback);
        $this->messageManager->expects($this->any())
            ->method('addExceptionMessage')->willReturnCallback($returnExceptionCallback);

        $this->controller->execute();

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function getQuoteIds()
    {
        $ph = new Phrase('test');
        return [
            [1, 2, 2, null, 'You have deleted the quote.'],
            [1, 2, 3, null, ''],
            [1, 2, 3, new \Exception(), 'We can\'t delete the quote right now.'],
            [1, 2, 3, new LocalizedException($ph), 'test'],
            [null, 2, 2, null, ''],
        ];
    }

    /**
     * Test for method execute without form key
     */
    public function testExecuteWithoutFormkey()
    {
        $result = $this->controller->execute();

        $this->assertInstanceOf(Redirect::class, $result);
    }
}
