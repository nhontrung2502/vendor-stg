<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrder\Block\PurchaseOrder;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Template;
use Magento\Customer\Api\CustomerNameGenerationInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderLogInterface;
use Magento\PurchaseOrder\Model\PurchaseOrder\LogManagementInterface;

/**
 * Block class for the history section of the purchase order details page.
 *
 * @api
 * @since 100.2.0
 */
class History extends Template
{
    /**
     * @var LogManagementInterface
     */
    private $purchaseOrderLogManagement;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CustomerNameGenerationInterface
     */
    private $customerNameGeneration;

    /**
     * @var array
     */
    private $actions;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * History block constructor.
     *
     * @param TemplateContext $context
     * @param LogManagementInterface $purchaseOrderLogManagement
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerNameGenerationInterface $customerNameGeneration
     * @param SerializerInterface $serializer
     * @param array $data
     * @param array $actions
     */
    public function __construct(
        TemplateContext $context,
        LogManagementInterface $purchaseOrderLogManagement,
        CustomerRepositoryInterface $customerRepository,
        CustomerNameGenerationInterface $customerNameGeneration,
        SerializerInterface $serializer,
        array $data = [],
        array $actions = []
    ) {
        parent::__construct($context, $data);

        $this->purchaseOrderLogManagement = $purchaseOrderLogManagement;
        $this->customerRepository = $customerRepository;
        $this->customerNameGeneration = $customerNameGeneration;
        $this->actions = $actions;
        $this->serializer = $serializer;
    }

    /**
     * Get logs for the purchase order currently being viewed.
     *
     * @return PurchaseOrderLogInterface[]
     * @since 100.2.0
     */
    public function getPurchaseOrderLogs()
    {
        $purchaseOrderId = $this->_request->getParam('request_id');

        return $this->purchaseOrderLogManagement->getPurchaseOrderLogs($purchaseOrderId);
    }

    /**
     * Get log message from log entry.
     *
     * @param PurchaseOrderLogInterface $log
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @since 100.2.0
     */
    public function getLogMessage(PurchaseOrderLogInterface $log)
    {
        $logData = $log->getRequestLog();
        try {
            $logData = $this->serializer->unserialize($logData);
        } catch (\Exception $e) {
            if (is_string($logData)) {
                return $logData;
            }
            return '';
        }
        if (!$logData || !is_array($logData) || !isset($this->actions[$logData['action']])) {
            return '';
        }
        $action = $logData['action'];
        $message = $this->actions[$action]['message'];
        $result = (string)__($message, $this->prepareParams($action, $logData['params']));
        if ($log->getOwnerId()) {
            $customer = $this->customerRepository->getById($log->getOwnerId());
            $creatorName = $this->customerNameGeneration->getCustomerName($customer);
            $result .= ' ' . __('By %1', $creatorName);
        }
        return $result;
    }

    /**
     * Prepare parameters for rendering.
     *
     * @param string $action
     * @param array $params
     * @return array
     */
    private function prepareParams($action, $params)
    {
        if (!empty($this->actions[$action]['translate_params'])) {
            foreach ($params as $key => $param) {
                if (in_array($key, $this->actions[$action]['translate_params'])) {
                    $params[$key] = __($param);
                }
            }
        }
        return $params;
    }
}
