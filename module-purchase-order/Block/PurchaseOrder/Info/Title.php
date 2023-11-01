<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrder\Block\PurchaseOrder\Info;

use Magento\Customer\Api\CustomerNameGenerationInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Block\PurchaseOrder\AbstractPurchaseOrder;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Block class for the general info title section of the purchase order details page.
 *
 * @api
 * @since 100.2.0
 */
class Title extends AbstractPurchaseOrder
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CustomerNameGenerationInterface
     */
    private $customerNameGeneration;

    /**
     * @param TemplateContext $context
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerNameGenerationInterface $customerNameGeneration
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        CartRepositoryInterface $quoteRepository,
        CustomerRepositoryInterface $customerRepository,
        CustomerNameGenerationInterface $customerNameGeneration,
        array $data = []
    ) {
        parent::__construct($context, $purchaseOrderRepository, $quoteRepository, $data);
        $this->customerRepository = $customerRepository;
        $this->customerNameGeneration = $customerNameGeneration;
    }

    /**
     * Get the name of the creator of the purchase order currently being viewed.
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @since 100.2.0
     */
    public function getCreatorName()
    {
        $purchaseOrder = $this->getPurchaseOrder();
        $creatorId = $purchaseOrder->getCreatorId();
        $creatorName = '';

        if ($creatorId) {
            $purchaseOrderCreator = $this->customerRepository->getById($purchaseOrder->getCreatorId());
            $creatorName = $this->customerNameGeneration->getCustomerName($purchaseOrderCreator);
        }

        return $creatorName;
    }
}
