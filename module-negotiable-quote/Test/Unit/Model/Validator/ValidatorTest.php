<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\Validator;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\NegotiableQuote\Model\Validator\Validator;
use Magento\NegotiableQuote\Model\Validator\ValidatorInterface;
use Magento\NegotiableQuote\Model\Validator\ValidatorResult;
use Magento\NegotiableQuote\Model\Validator\ValidatorResultFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Validator.
 */
class ValidatorTest extends TestCase
{
    /**
     * @var ValidatorInterface|MockObject
     */
    private $validatorMock;

    /**
     * @var ValidatorResultFactory|MockObject
     */
    private $validatorResultFactory;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->validatorMock = $this
            ->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->validatorResultFactory = $this
            ->getMockBuilder(ValidatorResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
    }

    /**
     * Test for validate().
     *
     * @return void
     */
    public function testValidate()
    {
        $validateConfig = [
            'action' => ['action' => 'action']
        ];
        $action = 'action';
        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->validator = $objectManagerHelper->getObject(
            Validator::class,
            [
                'validators' => [$action => $this->validatorMock],
                'validatorResultFactory' => $this->validatorResultFactory,
                'validateConfig' => $validateConfig,
                'action' => $action,
            ]
        );

        $data = [];
        $this->prepareMocks($data);

        $this->assertInstanceOf(
            ValidatorResult::class,
            $this->validator->validate($data)
        );
    }

    /**
     * Test for validate() method when his $action property is empty.
     *
     * @return void
     */
    public function testValidateIfActionIsEmpty()
    {
        $validateConfig = [];
        $action = '';
        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->validator = $objectManagerHelper->getObject(
            Validator::class,
            [
                'validators' => [$action => $this->validatorMock],
                'validatorResultFactory' => $this->validatorResultFactory,
                'validateConfig' => $validateConfig,
                'action' => $action,
            ]
        );

        $data = [];
        $this->prepareMocks($data);

        $this->assertInstanceOf(
            ValidatorResult::class,
            $this->validator->validate($data)
        );
    }

    /**
     * Prepare mocks for validate() method tests.
     *
     * @param array $data
     * @return void
     */
    private function prepareMocks($data)
    {
        $resultMock = $this->getMockBuilder(ValidatorResult::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultMock->expects($this->atLeastOnce())->method('hasMessages')->willReturn(true);
        $this->validatorResultFactory->expects($this->atLeastOnce())->method('create')
            ->willReturn($resultMock);
        $this->validatorMock->expects($this->atLeastOnce())->method('validate')->with($data)
            ->willReturn($resultMock);
    }

    /**
     * Test for validate() method when his $action property is empty.
     *
     * @return void
     */
    public function testValidateIfActionIsNotEmpty()
    {
        $validateConfig = [];
        $action = 'some_action';
        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->validator = $objectManagerHelper->getObject(
            Validator::class,
            [
                'validators' => [$action => $this->validatorMock],
                'validatorResultFactory' => $this->validatorResultFactory,
                'validateConfig' => $validateConfig,
                'action' => $action,
            ]
        );

        $data = [];
        $resultMock = $this->getMockBuilder(ValidatorResult::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validatorResultFactory->expects($this->atLeastOnce())->method('create')
            ->willReturn($resultMock);

        $this->assertInstanceOf(
            ValidatorResult::class,
            $this->validator->validate($data)
        );
    }
}
