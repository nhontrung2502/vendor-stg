<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Plugin\Quote\Model;

use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\SalesRule\Model\Coupon\Quote\UpdateCouponUsages;
use Magento\Quote\Api\Data\CartInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;

/**
 * Plugin Class to prevent updating coupon usages process for removed sales rules in Purchase Order Quote
 */
class UpdateCouponUsagesPlugin
{
    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * @var RuleRepositoryInterface
     */
    private $ruleRepository;

    /**
     * UpdateCouponUsagesPlugin constructor.
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param RuleRepositoryInterface $ruleRepository
     */
    public function __construct(
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        RuleRepositoryInterface $ruleRepository
    ) {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->ruleRepository = $ruleRepository;
    }

    /**
     * Prevent coupon usages execution for Purchase Order Quote removed rules
     *
     * @param UpdateCouponUsages $subject
     * @param CartInterface $quote
     * @param bool $increment
     * @return array
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeExecute(
        UpdateCouponUsages $subject,
        CartInterface $quote,
        bool $increment
    ) {
        if ($this->isPurchaseOrderQuote($quote) && $quote->getAppliedRuleIds()) {
            $appliedRuleIds = explode(',', $quote->getAppliedRuleIds());
            $appliedRulesToExecute = [];
            foreach ($appliedRuleIds as $appliedRuleId) {
                try {
                    $rule = $this->ruleRepository->getById($appliedRuleId);
                    $appliedRulesToExecute[] = $rule->getRuleId();
                } catch (NoSuchEntityException $e) {
                    continue;
                }
            }
            $quote->setAppliedRuleIds(implode(',', $appliedRulesToExecute));
        }
        return [$quote, $increment];
    }

    /**
     * Check if quote is using for Purchase Order
     *
     * @param CartInterface $quote
     * @return bool
     */
    private function isPurchaseOrderQuote(CartInterface $quote)
    {
        $purchaseOrder = $this->purchaseOrderRepository->getByQuoteId($quote->getId());
        return ($purchaseOrder->getEntityId()) ? true : false;
    }
}
