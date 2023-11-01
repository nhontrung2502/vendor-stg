<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Model\Quote\History as NegotiableQuoteHistory;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderManagementInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderRepositoryInterface;
use Magento\PurchaseOrder\Model\Notification\Action\ApprovalRequired;
use Magento\PurchaseOrder\Model\Notification\Action\ApprovalAndPaymentDetailsRequired;
use Magento\PurchaseOrder\Model\Notification\Action\RequestApproval;
use Magento\PurchaseOrder\Model\Notification\Action\Approved;
use Magento\PurchaseOrder\Model\Notification\Action\OrderPlacementFailed;
use Magento\PurchaseOrder\Model\Notification\Action\ApprovedPaymentDetailsRequired;
use Magento\PurchaseOrder\Model\Notification\Action\AutoApproved;
use Magento\PurchaseOrder\Model\Notification\Action\AutoApprovedPendingPayment;
use Magento\PurchaseOrder\Model\Notification\Action\Rejected;
use Magento\PurchaseOrder\Model\Notification\NotifierInterface as NotifierInterface;
use Magento\PurchaseOrder\Model\Validator\ActionReady\ValidatorLocator;
use Magento\PurchaseOrder\Model\Validator\Exception\PurchaseOrderValidationException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Store\Model\StoreManagerInterface;
use Magento\PurchaseOrder\Model\Payment\DeferredPaymentStrategyInterface;
use Magento\Framework\App\ObjectManager;
use Psr\Log\LoggerInterface as GenericLoggerInterface;

/**
 * Class manages purchase orders.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PurchaseOrderManagement implements PurchaseOrderManagementInterface
{
    /**
     * @var PurchaseOrderRepositoryInterface
     */
    private $purchaseOrderRepository;

    /**
     * @var PurchaseOrder\LogManagement
     */
    private $purchaseOrderLogManagement;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var ValidatorLocator
     */
    private $validatorLocator;

    /**
     * @var GenericLoggerInterface
     */
    private $logger;

    /**
     * @var PublisherInterface
     */
    private $queuePublisher;

    /**
     * @var NotifierInterface
     */
    private $notifier;

    /**
     * @var OrderSender
     */
    private $orderEmailSender;

    /**
     * @var NegotiableQuoteRepositoryInterface
     */
    private $negotiableQuoteRepository;

    /**
     * @var NegotiableQuoteHistory
     */
    private $negotiableQuoteHistory;

    /**
     * @var DeferredPaymentStrategyInterface
     */
    private $deferredPaymentStrategy;

    /**
     * @param PublisherInterface $publisher
     * @param PurchaseOrder\LogManagementInterface $purchaseOrderLogManagement
     * @param PurchaseOrderRepositoryInterface $purchaseOrderRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param CartManagementInterface $cartManagement
     * @param StoreManagerInterface $storeManager
     * @param OrderRepositoryInterface $orderRepository
     * @param ValidatorLocator $validatorLocator
     * @param GenericLoggerInterface $logger
     * @param NotifierInterface $notifier
     * @param OrderSender $orderEmailSender
     * @param NegotiableQuoteRepositoryInterface $negotiableQuoteRepository
     * @param NegotiableQuoteHistory $negotiableQuoteHistory
     * @param DeferredPaymentStrategyInterface|null $deferredPaymentStrategy
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        PublisherInterface $publisher,
        PurchaseOrder\LogManagementInterface $purchaseOrderLogManagement,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        CartRepositoryInterface $quoteRepository,
        CartManagementInterface $cartManagement,
        StoreManagerInterface $storeManager,
        OrderRepositoryInterface $orderRepository,
        ValidatorLocator $validatorLocator,
        GenericLoggerInterface $logger,
        NotifierInterface $notifier,
        OrderSender $orderEmailSender,
        NegotiableQuoteRepositoryInterface $negotiableQuoteRepository,
        NegotiableQuoteHistory $negotiableQuoteHistory,
        DeferredPaymentStrategyInterface $deferredPaymentStrategy = null
    ) {
        $this->purchaseOrderLogManagement = $purchaseOrderLogManagement;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->queuePublisher = $publisher;
        $this->quoteRepository = $quoteRepository;
        $this->cartManagement = $cartManagement;
        $this->storeManager = $storeManager;
        $this->orderRepository = $orderRepository;
        $this->validatorLocator = $validatorLocator;
        $this->logger = $logger;
        $this->notifier = $notifier;
        $this->orderEmailSender = $orderEmailSender;
        $this->negotiableQuoteHistory = $negotiableQuoteHistory;
        $this->negotiableQuoteRepository = $negotiableQuoteRepository;
        $this->deferredPaymentStrategy = $deferredPaymentStrategy ??
            ObjectManager::getInstance()->get(DeferredPaymentStrategyInterface::class);
    }

    /**
     * @inheritdoc
     */
    public function createSalesOrder(PurchaseOrderInterface $purchaseOrder, $actorId = null) : OrderInterface
    {
        if (!$this->validatorLocator->getValidator('placeorder')->validate($purchaseOrder)) {
            throw new LocalizedException(
                __(
                    'Order cannot be placed with purchase order #%1.',
                    $purchaseOrder->getIncrementId()
                )
            );
        };

        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_ORDER_IN_PROGRESS);
        $this->purchaseOrderRepository->save($purchaseOrder);

        try {
            $quote = $this->quoteRepository->get($purchaseOrder->getQuoteId());
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(
                __(
                    'Order cannot be placed with purchase order #%1.',
                    $purchaseOrder->getIncrementId()
                )
            );
        }

        try {
            $this->storeManager->setCurrentStore($quote->getStore()->getId());
            $order = $this->placeOrder($quote);
            $purchaseOrder->setOrderId($order->getId());
            $purchaseOrder->setOrderIncrementId($order->getIncrementId());
            $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_ORDER_PLACED);
            $this->purchaseOrderRepository->save($purchaseOrder);
            $this->purchaseOrderLogManagement->logAction(
                $purchaseOrder,
                'place_order',
                [
                    'increment_id' => $purchaseOrder->getIncrementId(),
                    'order_increment_id' => $order->getIncrementId()
                ],
                $actorId
            );

            /** @var NegotiableQuoteInterface $negotiableQuote */
            $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();

            if ($negotiableQuote !== null && $negotiableQuote->getQuoteId() !== null) {
                $negotiableQuote->setStatus(NegotiableQuoteInterface::STATUS_ORDERED);
                $this->quoteRepository->save($quote);
                $this->negotiableQuoteHistory->updateLog($negotiableQuote->getQuoteId());
            }

            $purchaseOrderIncrementId = $purchaseOrder->getIncrementId();
            $orderIncrementId = $order->getIncrementId();
            $grandTotal = $quote->getGrandTotal();
            $this->logger->info(
                "Purchase Order Id: {$purchaseOrderIncrementId} & Order Id: {$orderIncrementId} & Total: {$grandTotal}"
            );
            $this->orderEmailSender->send($order);

            /** @var NegotiableQuoteInterface $negotiableQuote */
            $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();

            if ($negotiableQuote !== null && $negotiableQuote->getQuoteId() !== null) {
                $negotiableQuote->setStatus(NegotiableQuoteInterface::STATUS_ORDERED);
                $this->quoteRepository->save($quote);
                $this->negotiableQuoteHistory->updateLog($negotiableQuote->getQuoteId());
            }
            return $order;
        } catch (LocalizedException $e) {
            $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_ORDER_FAILED);
            $this->failOrderPlace($purchaseOrder, $e->getMessage());
            throw $e;
        } catch (\Exception $exception) {
            $this->logger->critical($exception);
            $this->failOrderPlace($purchaseOrder, 'An error occurred on the server. Please try again.');
            throw new LocalizedException(
                __('An error occurred on the server. Please try again.'),
                $exception
            );
        }
    }

    /**
     * Process order placement failure.
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @param string $message
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     */
    private function failOrderPlace(PurchaseOrderInterface $purchaseOrder, $message) : void
    {
        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_ORDER_FAILED);
        $this->purchaseOrderRepository->save($purchaseOrder);
        $this->purchaseOrderLogManagement->logAction(
            $purchaseOrder,
            'place_order_fail',
            [
                'increment_id' => $purchaseOrder->getIncrementId(),
                'error_message' => $message
            ]
        );
        $this->notifier->notifyOnAction(
            (int)$purchaseOrder->getEntityId(),
            OrderPlacementFailed::class
        );
    }

    /**
     * @inheritdoc
     */
    public function cancelPurchaseOrder(PurchaseOrderInterface $purchaseOrder, $actorId = null) : void
    {
        if (!$this->validatorLocator->getValidator('cancel')->validate($purchaseOrder)) {
            throw new PurchaseOrderValidationException(
                __(
                    'Purchase order %1 cannot be canceled.',
                    $purchaseOrder->getIncrementId()
                )
            );
        }

        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_CANCELED);
        $this->purchaseOrderRepository->save($purchaseOrder);
        $this->purchaseOrderLogManagement->logAction(
            $purchaseOrder,
            "cancel",
            [
                'increment_id' => $purchaseOrder->getIncrementId()
            ],
            $actorId
        );
        $this->closeRelatedNegotiableQuote($purchaseOrder);
    }

    /**
     * Close linked negotiable quote if exists.
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    private function closeRelatedNegotiableQuote(PurchaseOrderInterface $purchaseOrder)
    {
        $negotiableQuote = $this->negotiableQuoteRepository->getById((int)$purchaseOrder->getQuoteId());
        if ($negotiableQuote->getQuoteId()) {
            $negotiableQuote->setStatus(NegotiableQuoteInterface::STATUS_CLOSED);
            $this->negotiableQuoteRepository->save($negotiableQuote);
            $this->negotiableQuoteHistory->updateLog($negotiableQuote->getQuoteId());
        }
    }

    /**
     * @inheritdoc
     */
    public function rejectPurchaseOrder(PurchaseOrderInterface $purchaseOrder, $actorId = null) : void
    {
        if (!$this->validatorLocator->getValidator('reject')->validate($purchaseOrder)) {
            throw new PurchaseOrderValidationException(
                __(
                    'Purchase order %1 cannot be rejected.',
                    $purchaseOrder->getIncrementId()
                )
            );
        }

        $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_REJECTED);
        $this->purchaseOrderRepository->save($purchaseOrder);
        $this->purchaseOrderLogManagement->logAction(
            $purchaseOrder,
            'reject',
            [
                'increment_id' => $purchaseOrder->getIncrementId()
            ],
            $actorId
        );
        $this->closeRelatedNegotiableQuote($purchaseOrder);
        $this->notifier->notifyOnAction(
            (int)$purchaseOrder->getEntityId(),
            Rejected::class
        );
    }

    /**
     * @inheritDoc
     */
    public function approvePurchaseOrder(PurchaseOrderInterface $purchaseOrder, $actorId = null) : void
    {
        $approvedStatuses = [
            PurchaseOrderInterface::STATUS_APPROVED,
            PurchaseOrderInterface::STATUS_APPROVED_PENDING_PAYMENT
        ];

        if (!in_array($purchaseOrder->getStatus(), $approvedStatuses)) {
            try {
                if (!$this->validatorLocator->getValidator('approve')->validate($purchaseOrder)) {
                    throw new PurchaseOrderValidationException(
                        __(
                            'Purchase order %1 cannot be approved.',
                            $purchaseOrder->getIncrementId()
                        )
                    );
                }

                // If the system is approving the order it means it was auto approved
                if ($actorId === null || $purchaseOrder->getCreatorId() == $actorId) {
                    $purchaseOrder->setAutoApproved(true);
                } else {
                    $purchaseOrder->setApprovedBy([$actorId]);
                }

                if ($this->deferredPaymentStrategy->isDeferredPayment($purchaseOrder)) {
                    $autoApprovedNotifier = AutoApprovedPendingPayment::class;
                    $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_APPROVED_PENDING_PAYMENT);
                } else {
                    $autoApprovedNotifier = AutoApproved::class;
                    $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_APPROVED);
                }

                $this->purchaseOrderRepository->save($purchaseOrder);
                if ($purchaseOrder->getAutoApproved()) {
                    $this->notifier->notifyOnAction(
                        (int)$purchaseOrder->getEntityId(),
                        $autoApprovedNotifier
                    );
                    $this->purchaseOrderLogManagement->logAction(
                        $purchaseOrder,
                        'auto_approve',
                        [
                            'increment_id' => $purchaseOrder->getIncrementId()
                        ]
                    );
                } else {
                    if ($this->deferredPaymentStrategy->isDeferredPayment($purchaseOrder)) {
                        $this->notifier->notifyOnAction(
                            (int)$purchaseOrder->getEntityId(),
                            ApprovedPaymentDetailsRequired::class
                        );
                    } else {
                        $this->notifier->notifyOnAction(
                            (int)$purchaseOrder->getEntityId(),
                            Approved::class
                        );
                    }
                    $this->purchaseOrderLogManagement->logAction(
                        $purchaseOrder,
                        "approve",
                        [
                            'increment_id' => $purchaseOrder->getIncrementId()
                        ],
                        $actorId
                    );
                }

                $this->queuePublisher->publish('purchaseorder.toorder', $purchaseOrder->getEntityId());
            } catch (LocalizedException $e) {
                throw new LocalizedException(__('Unable to approve purchase order. %1', $e->getMessage()));
            }
        }
    }

    /**
     * Place order
     *
     * @param CartInterface $quote
     * @return OrderInterface
     * @throws LocalizedException
     */
    private function placeOrder(CartInterface $quote)
    {
        $quote->setIsActive(true);
        $quote->setTotalsCollectedFlag(true);
        $this->quoteRepository->save($quote);

        try {
            $orderId = $this->cartManagement->placeOrder($quote->getId());
            $order = $this->orderRepository->get($orderId);
        } catch (LocalizedException $e) {
            $quote->setIsActive(false);
            $this->quoteRepository->save($quote);
            throw $e;
        } catch (\Exception $e) {
            $quote->setIsActive(false);
            $this->quoteRepository->save($quote);
            $this->logger->critical($e);
            throw new LocalizedException(
                __('An error occurred on the server. Please try again.'),
                $e
            );
        }
        $quote->setIsActive(false);
        $this->quoteRepository->save($quote);
        return $order;
    }

    /**
     * @inheritDoc
     */
    public function setApprovalRequired(PurchaseOrderInterface $purchaseOrder): void
    {
        try {
            $purchaseOrder->setStatus(PurchaseOrderInterface::STATUS_APPROVAL_REQUIRED);
            $this->purchaseOrderRepository->save($purchaseOrder);
            $this->notifier->notifyOnAction(
                (int)$purchaseOrder->getEntityId(),
                RequestApproval::class
            );
            if ($this->deferredPaymentStrategy->isDeferredPayment($purchaseOrder)) {
                $this->notifier->notifyOnAction(
                    (int)$purchaseOrder->getEntityId(),
                    ApprovalAndPaymentDetailsRequired::class
                );
            } else {
                $this->notifier->notifyOnAction(
                    (int)$purchaseOrder->getEntityId(),
                    ApprovalRequired::class
                );
            }
        } catch (LocalizedException $e) {
            throw new LocalizedException(
                __(
                    'Unable to set purchase order approval required status. %1',
                    $e->getMessage()
                )
            );
        }
    }
}
