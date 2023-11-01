<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model\History;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\History\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->validator = $objectManager->getObject(Validator::class);
    }

    /**
     * Test for validate() method
     *
     * @return void
     */
    public function testValidate()
    {
        $expectedResult = [
            '"Negotiable quote ID" is required. Enter and try again.',
            '"Author ID" is required. Enter and try again.'
        ];
        $objectMock = $this->getMockBuilder(AbstractModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['hasData'])
            ->getMock();
        $objectMock->expects($this->any())
            ->method('hasData')
            ->willReturn(false);
        $result = $this->validator->validate($objectMock);
        $this->assertEquals($expectedResult, $result);
    }
}
