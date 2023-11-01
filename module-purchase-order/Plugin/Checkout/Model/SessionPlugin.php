<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Plugin\Checkout\Model;

use Magento\Checkout\Model\Session;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\RequestInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;

/**
 * Plugin for changing current quote id in checkout session.
 */
class SessionPlugin
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * SessionPlugin constructor.
     *
     * @param CartRepositoryInterface $quoteRepository
     * @param RequestInterface $request
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        RequestInterface $request,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->request = $request;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
    }

    /**
     * Changing current quote id in checkout session.
     * Change quote id to purchase order quote if checkout from quote is processing.
     * Change quote id to null if current quote is negotiable and checkout from quote is't processing.
     *
     * @param Session $subject
     * @param int $quoteId
     * @return int|null
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetQuoteId(Session $subject, $quoteId)
    {
        $purchaseOrderId = $this->request->getParam('purchaseOrderId');
        try {
            if ($purchaseOrderId) {
                $purchaseOrder = $this->purchaseOrderRepository->getById($purchaseOrderId);
                $quote = $this->quoteRepository->get($purchaseOrder->getQuoteId());
                $quote->setIsActive(true);
                $quoteId = $purchaseOrder->getQuoteId();
            } elseif (!empty($quoteId)) {
                $quote = $this->quoteRepository->get($quoteId);
                if ($quote && $this->isPurchaseOrderQuote($quoteId)) {
                    $quote->setIsActive(false);
                    $this->quoteRepository->save($quote);
                    $quoteId = null;
                }
            }
        } catch (NoSuchEntityException $e) {
            return $quoteId;
        }

        return $quoteId;
    }

    /**
     * Check is purchase order quote
     *
     * @param int $quoteId
     * @return bool
     */
    private function isPurchaseOrderQuote($quoteId)
    {
        $purchaseOrder = $this->purchaseOrderRepository->getByQuoteId($quoteId);
        return (bool)$purchaseOrder->getEntityId();
    }
}
