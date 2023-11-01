<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NegotiableQuote\Test\Unit\Model;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Model\Url;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Model\Config;
use Magento\NegotiableQuote\Model\SettingsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SettingsProviderTest extends TestCase
{
    /**
     * @var Config|MockObject
     */
    private $moduleConfig;

    /**
     * @var Url|MockObject
     */
    private $customerUrl;

    /**
     * @var JsonFactory|MockObject
     */
    private $resultJsonFactory;

    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var SettingsProvider
     */
    private $settingsProvider;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->moduleConfig = $this->createMock(Config::class);
        $this->customerUrl = $this->createMock(Url::class);
        $this->resultJsonFactory =
            $this->createPartialMock(JsonFactory::class, ['create']);
        $this->userContext = $this->getMockForAbstractClass(UserContextInterface::class);
        $objectManager = new ObjectManager($this);
        $this->settingsProvider = $objectManager->getObject(
            SettingsProvider::class,
            [
                'moduleConfig' => $this->moduleConfig,
                'customerUrl' => $this->customerUrl,
                'resultJsonFactory' => $this->resultJsonFactory,
                'userContext' => $this->userContext,
            ]
        );
    }

    /**
     * Test isModuleEnabled
     *
     * @param bool $isModuleEnabled
     * @dataProvider dataProviderIsModuleEnabled
     */
    public function testIsModuleEnabled($isModuleEnabled)
    {
        $this->moduleConfig->expects($this->any())->method('isActive')->willReturn($isModuleEnabled);

        $this->assertEquals($isModuleEnabled, $this->settingsProvider->isModuleEnabled());
    }

    /**
     * Test getCustomerLoginUrl
     */
    public function testGetCustomerLoginUrl()
    {
        $customerUrl = 'customer_url';
        $this->customerUrl->expects($this->any())->method('getLoginUrl')->willReturn($customerUrl);

        $this->assertEquals($customerUrl, $this->settingsProvider->getCustomerLoginUrl());
    }

    /**
     * Test retrieveJsonSuccess
     */
    public function testRetrieveJsonSuccess()
    {
        $resultJson = $this->getResultJsonMock();

        $this->assertEquals($resultJson, $this->settingsProvider->retrieveJsonSuccess([]));
    }

    /**
     * Test retrieveJsonError
     */
    public function testRetrieveJsonError()
    {
        $resultJson = $this->getResultJsonMock();

        $this->assertEquals($resultJson, $this->settingsProvider->retrieveJsonError());
    }

    /**
     * Test getCurrentUserId
     */
    public function testGetCurrentUserId()
    {
        $userId = 1;
        $this->userContext->expects($this->any())->method('getUserId')->willReturn($userId);

        $this->assertEquals($userId, $this->settingsProvider->getCurrentUserId());
    }

    /**
     * getCurrentUserType
     */
    public function testGetCurrentUserType()
    {
        $userType = 'user_type';
        $this->userContext->expects($this->any())->method('getUserType')->willReturn($userType);

        $this->assertEquals($userType, $this->settingsProvider->getCurrentUserType());
    }

    /**
     * Test isModuleEnabled
     *
     * @return array
     */
    public function dataProviderIsModuleEnabled()
    {
        return [
            [true],
            [false]
        ];
    }

    /**
     * Get result json mock
     *
     * @return Json|MockObject
     */
    private function getResultJsonMock()
    {
        $resultJson = $this->createMock(Json::class);
        $resultJson->expects($this->any())->method('setData')->willReturnSelf();
        $this->resultJsonFactory->expects($this->any())->method('create')->willReturn($resultJson);

        return $resultJson;
    }
}
