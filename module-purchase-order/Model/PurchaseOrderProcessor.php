<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model;

use Magento\Checkout\Model\Session;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Model\Quote\History as NegotiableQuoteHistory;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Model\PurchaseOrder\LogManagement;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Processor for purchase order processing.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class PurchaseOrderProcessor implements ProcessorInterface
{
    /**
     * @var PurchaseOrderFactory
     */
    private $purchaseOrderFactory;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var PurchaseOrderQuoteConverter
     */
    private $quoteConverter;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * @var LogManagement
     */
    private $purchaseOrderLogManagement;

    /**
     * @var CompanyManagementInterface
     */
    private $companyManagement;

    /**
     * @var NegotiableQuoteRepositoryInterface
     */
    private $negotiableQuoteRepository;

    /**
     * @var NegotiableQuoteHistory
     */
    private $negotiableQuoteHistory;

    /**
     * PurchaseOrderProcessor constructor.
     *
     * @param PurchaseOrderFactory $purchaseOrderFactory
     * @param Session $checkoutSession
     * @param PurchaseOrderQuoteConverter $quoteConverter
     * @param LogManagement $purchaseOrderLogManagement
     * @param CartRepositoryInterface $quoteRepository
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param CompanyManagementInterface $companyManagement
     * @param NegotiableQuoteRepositoryInterface $negotiableQuoteRepository
     * @param NegotiableQuoteHistory $negotiableQuoteHistory
     */
    public function __construct(
        PurchaseOrderFactory $purchaseOrderFactory,
        Session $checkoutSession,
        PurchaseOrderQuoteConverter $quoteConverter,
        LogManagement $purchaseOrderLogManagement,
        CartRepositoryInterface $quoteRepository,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        CompanyManagementInterface $companyManagement,
        NegotiableQuoteRepositoryInterface $negotiableQuoteRepository,
        NegotiableQuoteHistory $negotiableQuoteHistory
    ) {
        $this->purchaseOrderFactory = $purchaseOrderFactory;
        $this->checkoutSession = $checkoutSession;
        $this->quoteConverter = $quoteConverter;
        $this->purchaseOrderLogManagement = $purchaseOrderLogManagement;
        $this->quoteRepository = $quoteRepository;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->companyManagement = $companyManagement;
        $this->negotiableQuoteRepository = $negotiableQuoteRepository;
        $this->negotiableQuoteHistory = $negotiableQuoteHistory;
    }

    /**
     * @inheritDoc
     */
    public function createPurchaseOrder(
        CartInterface $quote,
        PaymentInterface $paymentMethod
    ) : PurchaseOrderInterface {
        $shippingMethod = $quote->getShippingAddress()->getShippingMethod();

        $purchaseOrder = $this->purchaseOrderFactory->create();
        $company = $this->companyManagement->getByCustomerId($quote->getCustomerId());

        $purchaseOrder
            ->setQuoteId($quote->getId())
            ->setStatus(PurchaseOrderInterface::STATUS_PENDING)
            ->setCompanyId($company->getId())
            ->setCreatorId($quote->getCustomerId())
            ->setShippingMethod($shippingMethod)
            ->setPaymentMethod($paymentMethod->getMethod())
            ->setGrandTotal($quote->getGrandTotal());

        try {
            $purchaseOrder->setSnapshotQuote($quote);
            $this->purchaseOrderRepository->save($purchaseOrder);
            $this->updateQuote($quote);
        } catch (LocalizedException $e) {
            throw new CouldNotSaveException(__($e->getMessage()), $e);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()), $e);
        }

        $negotiableQuote = $this->negotiableQuoteRepository->getById($quote->getId());

        if ($negotiableQuote->getQuoteId()) {
            $this->negotiableQuoteHistory->updateLog(
                $negotiableQuote->getQuoteId(),
                false,
                'Purchase Order in Progress'
            );
        }

        $this->purchaseOrderLogManagement->logAction(
            $purchaseOrder,
            'submit',
            [
                'increment_id' => $purchaseOrder->getIncrementId()
            ],
            $purchaseOrder->getCreatorId()
        );
        $this->checkoutSession->setCurrentPurchaseOrderId($purchaseOrder->getEntityId());

        return $purchaseOrder;
    }

    /**
     * Update quote's prices and shipping rates, and set to inactive
     *
     * @param CartInterface $quote
     * @return void
     */
    private function updateQuote(CartInterface $quote)
    {
        // Lock in quote item prices via setting of original custom price
        foreach ($quote->getAllItems() as $quoteItem) {
            $quoteItem->setCustomPrice($quoteItem->getConvertedPrice());
            $quoteItem->setOriginalCustomPrice($quoteItem->getConvertedPrice());
            $quoteItem->getProduct()->setIsSuperMode(true);
        }

        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->setTotalsCollectedFlag(false)->collectTotals();

        // Set quote as inactive, thereby preventing further modification
        $quote->setIsActive(false);

        $this->quoteRepository->save($quote);
    }
}
