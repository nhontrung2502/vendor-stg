<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Notification\Email\ContentSource;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\DataObject;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Model\Notification\ContentSourceInterface;
use Magento\Company\Model\Company\Structure;

/**
 * Content source for approval and payment details required notification.
 */
class ApprovalAndPaymentDetailsRequiredAction implements ContentSourceInterface
{
    /**
     * Path to config value for template.
     */
    private const XML_PATH_TO_TEMPLATE =
        'sales_email/purchase_order_notification/purchase_order_approval_required_payment_details';

    /**
     * @var PurchaseOrderInterface
     */
    private $purchaseOrder;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var NegotiableQuoteRepositoryInterface
     */
    private $negotiableQuoteRepository;

    /**
     * @var int
     */
    private $recipientId;

    /**
     * @var Structure
     */
    private $structure;

    /**
     * @param PurchaseOrderInterface $purchaseOrder
     * @param CustomerRepositoryInterface $customerRepository
     * @param NegotiableQuoteRepositoryInterface $negotiableQuoteRepository
     * @param Structure $structure
     * @param int $recipientId
     */
    public function __construct(
        PurchaseOrderInterface $purchaseOrder,
        CustomerRepositoryInterface $customerRepository,
        NegotiableQuoteRepositoryInterface $negotiableQuoteRepository,
        Structure $structure,
        int $recipientId
    ) {
        $this->recipientId = $recipientId;
        $this->purchaseOrder = $purchaseOrder;
        $this->customerRepository = $customerRepository;
        $this->negotiableQuoteRepository = $negotiableQuoteRepository;
        $this->structure = $structure;
    }

    /**
     * @inheritDoc
     */
    public function getTemplateVars(): DataObject
    {
        $approvers = $this->getApprovers();
        $approversFullNames = $approvers[0]->getFirstname() . ' ' . $approvers[0]->getLastname();
        $recipient = $this->customerRepository->getById($this->recipientId);
        $negotiableQuote = $this->negotiableQuoteRepository->getById($this->purchaseOrder->getQuoteId());
        $data = [
            'recipient_email' => $recipient->getEmail(),
            'recipient_full_name' => $recipient->getFirstname() . ' ' . $recipient->getLastname(),
            'approvers_full_names' => $approversFullNames,
            'purchase_order_id' => $this->purchaseOrder->getEntityId(),
            'purchase_order_increment_id' => $this->purchaseOrder->getIncrementId(),
            'quote_id' => $negotiableQuote->getQuoteId(),
            'quote_name' => $negotiableQuote->getQuoteName()
        ];
        return new \Magento\Framework\DataObject($data);
    }

    /**
     * @inheritDoc
     */
    public function getTemplateConfigPath(): string
    {
        return self::XML_PATH_TO_TEMPLATE;
    }

    /**
     * @inheritDoc
     */
    public function getStoreId(): int
    {
        return (int)$this->purchaseOrder->getSnapshotQuote()
            ->getStoreId();
    }

    /**
     * Get approvers
     *
     * @return CustomerInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getApprovers(): array
    {
        $structure = $this->structure->getStructureByCustomerId($this->purchaseOrder->getCreatorId());
        $parentNode = $this->structure->getTreeById($structure->getParentId());
        $approvers = [$this->customerRepository->getById($parentNode->getEntityId())];
        return $approvers;
    }
}
