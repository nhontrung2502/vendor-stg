<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Block\PurchaseOrder\Info;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Block\PurchaseOrder\AbstractPurchaseOrder;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Block for displaying link to negotiable quote the purchase order was created from.
 *
 * @api
 * @since 100.2.0
 */
class NegotiableQuote extends AbstractPurchaseOrder
{
    /**
     * @var NegotiableQuoteRepositoryInterface
     */
    private $negotiableQuoteRepository;

    /**
     * NegotiableQuote constructor.
     *
     * @param TemplateContext $context
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param NegotiableQuoteRepositoryInterface $negotiableQuoteRepository
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        CartRepositoryInterface $quoteRepository,
        NegotiableQuoteRepositoryInterface $negotiableQuoteRepository,
        array $data = []
    ) {
        parent::__construct($context, $purchaseOrderRepository, $quoteRepository, $data);
        $this->negotiableQuoteRepository = $negotiableQuoteRepository;
    }

    /**
     * Is current purchase order created from negotiable quote.
     *
     * @return bool
     * @since 100.2.0
     */
    public function isFromNegotiableQuote()
    {
        $negotiableQuote = $this->getNegotiableQuote();
        if ($negotiableQuote !== null && $negotiableQuote->getQuoteId() !== null) {
            return true;
        }
        return false;
    }

    /**
     * Get Negotiable quote instance for current purchase order.
     *
     * @return \Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface|null
     * @since 100.2.0
     */
    public function getNegotiableQuote()
    {
        $quote = $this->getQuote();
        try {
            return $this->negotiableQuoteRepository->getById($quote->getId());
        } catch (NoSuchEntityException $exception) {
            return null;
        }
    }

    /**
     * Get URL for negotiable quote view.
     *
     * @param int $quoteId
     * @return string
     * @since 100.2.0
     */
    public function getNegotiableQuoteUrl($quoteId)
    {
        return $this->getUrl('negotiable_quote/quote/view/', ['quote_id' => $quoteId, '_secure' => true]);
    }
}
