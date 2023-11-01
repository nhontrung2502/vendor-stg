<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrder\Block\PurchaseOrder;

use Magento\Customer\Api\CustomerNameGenerationInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\PurchaseOrder\Api\Data\CommentInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Model\CommentManagement;
use Magento\PurchaseOrder\Model\ResourceModel\Comment\Collection as CommentCollection;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Block class for the comments section of the purchase order details page.
 *
 * @api
 * @since 100.2.0
 */
class Comments extends AbstractPurchaseOrder
{
    /**
     * @var CommentManagement
     */
    private $commentManagement;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CustomerNameGenerationInterface
     */
    private $customerNameGeneration;

    /**
     * Comments constructor.
     *
     * @param TemplateContext $context
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param CommentManagement $commentManagement
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerNameGenerationInterface $customerNameGeneration
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        CartRepositoryInterface $quoteRepository,
        CommentManagement $commentManagement,
        CustomerRepositoryInterface $customerRepository,
        CustomerNameGenerationInterface $customerNameGeneration,
        array $data = []
    ) {
        parent::__construct($context, $purchaseOrderRepository, $quoteRepository, $data);
        $this->commentManagement = $commentManagement;
        $this->customerRepository = $customerRepository;
        $this->customerNameGeneration = $customerNameGeneration;
    }

    /**
     * Get the request id for the purchase order currently being viewed.
     *
     * @return string
     * @since 100.2.0
     */
    public function getRequestId()
    {
        return $this->_request->getParam('request_id');
    }

    /**
     * Get the comments for the purchase order currently being viewed.
     *
     * @return CommentCollection
     * @since 100.2.0
     */
    public function getPurchaseOrderComments()
    {
        $purchaseOrderId = $this->_request->getParam('request_id');

        return $this->commentManagement->getPurchaseOrderComments($purchaseOrderId);
    }

    /**
     * Get the name of the creator of the specified comment.
     *
     * @param CommentInterface $comment
     * @return string
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @since 100.2.0
     */
    public function getCommentCreator(CommentInterface $comment)
    {
        $creatorName = '';

        if ($comment->getCreatorId()) {
            $customer = $this->customerRepository->getById($comment->getCreatorId());
            $author = $this->customerNameGeneration->getCustomerName($customer);
            $creatorName = '(' . $author . ')';
        }

        return $creatorName;
    }
}
