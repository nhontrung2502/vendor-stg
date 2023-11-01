<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model;

use Exception;
use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Company\Api\AuthorizationInterface;
use Magento\Company\Model\CompanyContext;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validator\Exception as ValidatorException;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderManagementInterface;
use Magento\PurchaseOrder\Api\PurchaseOrderPaymentInformationManagementInterface;
use Magento\PurchaseOrder\Model\Config as PurchaseOrderConfig;
use Magento\PurchaseOrder\Model\Validator\ValidatorInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface as QuoteAddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\CustomerManagement;
use Magento\Quote\Model\Quote as QuoteEntity;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteValidator;
use Psr\Log\LoggerInterface;

/**
 * Payment and shipping information management implementation.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class PurchaseOrderPaymentInformationManagement implements PurchaseOrderPaymentInformationManagementInterface
{
    /**
     * @var PaymentInformationManagementInterface
     */
    private $paymentInformationManagement;

    /**
     * @var PurchaseOrderProcessor
     */
    private $purchaseOrderProcessor;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CustomerManagement
     */
    private $customerManagement;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var QuoteValidator
     */
    private $quoteValidator;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var PurchaseOrderRepository
     */
    private $purchaseOrderRepository;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ValidatorInterface
     */
    private $purchaseOrderValidator;

    /**
     * @var PurchaseOrderConfig
     */
    private $purchaseOrderConfig;

    /**
     * @var PurchaseOrderManagementInterface
     */
    private $purchaseOrderManagement;

    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @var CompanyContext
     */
    private $companyContext;

    /**
     * @param PaymentInformationManagementInterface $paymentInformationManagement
     * @param PurchaseOrderProcessor $purchaseOrderProcessor
     * @param QuoteFactory $quoteFactory
     * @param LoggerInterface $logger
     * @param CustomerManagement $customerManagement
     * @param CustomerRepositoryInterface $customerRepository
     * @param AddressRepositoryInterface $addressRepository
     * @param QuoteValidator $quoteValidator
     * @param CartRepositoryInterface $quoteRepository
     * @param PurchaseOrderRepository $purchaseOrderRepository
     * @param ResourceConnection $resourceConnection
     * @param ValidatorInterface $purchaseOrderValidator
     * @param PurchaseOrderConfig $purchaseOrderConfig
     * @param PurchaseOrderManagementInterface $purchaseOrderManagement
     * @param AuthorizationInterface $authorization
     * @param CompanyContext $companyContext
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        PaymentInformationManagementInterface $paymentInformationManagement,
        PurchaseOrderProcessor $purchaseOrderProcessor,
        QuoteFactory $quoteFactory,
        LoggerInterface $logger,
        CustomerManagement $customerManagement,
        CustomerRepositoryInterface $customerRepository,
        AddressRepositoryInterface $addressRepository,
        QuoteValidator $quoteValidator,
        CartRepositoryInterface $quoteRepository,
        PurchaseOrderRepository $purchaseOrderRepository,
        ResourceConnection $resourceConnection,
        ValidatorInterface $purchaseOrderValidator,
        PurchaseOrderConfig $purchaseOrderConfig,
        PurchaseOrderManagementInterface $purchaseOrderManagement,
        AuthorizationInterface $authorization,
        CompanyContext $companyContext
    ) {
        $this->paymentInformationManagement = $paymentInformationManagement;
        $this->purchaseOrderProcessor = $purchaseOrderProcessor;
        $this->quoteFactory = $quoteFactory;
        $this->logger = $logger;
        $this->customerManagement = $customerManagement;
        $this->customerRepository = $customerRepository;
        $this->addressRepository = $addressRepository;
        $this->quoteValidator = $quoteValidator;
        $this->quoteRepository = $quoteRepository;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->resourceConnection = $resourceConnection;
        $this->purchaseOrderValidator = $purchaseOrderValidator;
        $this->purchaseOrderConfig = $purchaseOrderConfig;
        $this->purchaseOrderManagement = $purchaseOrderManagement;
        $this->authorization = $authorization;
        $this->companyContext = $companyContext;
    }

    /**
     * @inheritDoc
     */
    public function savePaymentInformationAndPlacePurchaseOrder(
        $cartId,
        PaymentInterface $paymentMethod,
        QuoteAddressInterface $billingAddress = null
    ) {
        $this->paymentInformationManagement->savePaymentInformation(
            $cartId,
            $paymentMethod,
            $billingAddress
        );

        $quote = $this->quoteFactory->create()->loadByIdWithoutStore($cartId);

        if (!$quote->getId()) {
            $this->logger->critical('Quote validation failed while saving Purchase Order.');
            throw new LocalizedException(__('There is some error while processing request, please try later.'));
        }

        if (!$this->purchaseOrderConfig->isEnabledForCustomer($quote->getCustomer())) {
            throw new AuthorizationException(__(
                'Customer is not a member of a company that has purchase orders enabled.'
            ));
        }

        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();

        try {
            $this->prepareCustomerQuote($quote);
            $this->quoteRepository->save($quote);
            $purchaseOrder = $this->purchaseOrderProcessor->createPurchaseOrder(
                $quote,
                $paymentMethod
            );
            $this->determineStatus($purchaseOrder);
            $connection->commit();
        } catch (LocalizedException $e) {
            $connection->rollBack();

            throw new CouldNotSaveException(
                __($e->getMessage()),
                $e
            );
        } catch (Exception $e) {
            $connection->rollBack();
            $this->logger->critical($e);

            throw new CouldNotSaveException(
                __('An error occurred on the server. Please try to place the Purchase Order again.'),
                $e
            );
        }

        return $purchaseOrder->getEntityId();
    }

    /**
     * Determine the status for the Purchase Order
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @throws LocalizedException
     * @throws Validator\Exception\PurchaseOrderValidationException
     */
    private function determineStatus(PurchaseOrderInterface $purchaseOrder)
    {
        if ($this->isCurrentUserExemptFromApproval()
            && (int)$this->companyContext->getCustomerId() === (int)$purchaseOrder->getCreatorId()
        ) {
            $this->purchaseOrderManagement->approvePurchaseOrder(
                $purchaseOrder,
                $this->companyContext->getCustomerId()
            );
        } else {
            $this->purchaseOrderValidator->validate($purchaseOrder);
        }
    }

    /**
     * Prepare quote for conversion to purchase order.
     *
     * @param QuoteEntity $quote
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws ValidatorException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function prepareCustomerQuote(QuoteEntity $quote)
    {
        $this->quoteValidator->validateBeforeSubmit($quote);

        $quoteBillingAddress = $quote->getBillingAddress();
        $quoteShippingAddress = $quote->isVirtual() ? null : $quote->getShippingAddress();

        $customer = $this->customerRepository->getById($quote->getCustomerId());

        $hasDefaultBillingAddress = (bool) $customer->getDefaultBilling();
        $hasDefaultShippingAddress = (bool) $customer->getDefaultShipping();

        if ($quoteShippingAddress && !$quoteShippingAddress->getSameAsBilling()
            && (!$quoteShippingAddress->getCustomerId() || $quoteShippingAddress->getSaveInAddressBook())
        ) {
            $customerShippingAddress = $quoteShippingAddress->exportCustomerAddress();

            if (!$hasDefaultShippingAddress) {
                // Make provided address default shipping address
                $customerShippingAddress->setIsDefaultShipping(true);
                $hasDefaultShippingAddress = true;
            }

            // save new customer address
            $customerShippingAddress->setCustomerId($quote->getCustomerId());
            $this->addressRepository->save($customerShippingAddress);

            $quote->addCustomerAddress($customerShippingAddress);

            $quoteShippingAddress
                ->setCustomerAddressData($customerShippingAddress)
                ->setCustomerAddressId($customerShippingAddress->getId())
                ->setSaveInAddressBook(false);
        }

        if (!$quoteBillingAddress->getCustomerId() || $quoteBillingAddress->getSaveInAddressBook()) {
            $customerBillingAddress = $quoteBillingAddress->exportCustomerAddress();

            if (!$hasDefaultBillingAddress) {
                if (!$hasDefaultShippingAddress) {
                    // Make provided address default shipping address
                    $customerBillingAddress->setIsDefaultShipping(true);
                }

                // Make provided address default billing address
                $customerBillingAddress->setIsDefaultBilling(true);
            }

            $customerBillingAddress->setCustomerId($quote->getCustomerId());
            $this->addressRepository->save($customerBillingAddress);

            $quote->addCustomerAddress($customerBillingAddress);

            $quoteBillingAddress
                ->setCustomerAddressData($customerBillingAddress)
                ->setCustomerAddressId($customerBillingAddress->getId())
                ->setSaveInAddressBook(false);
        }

        if ($quoteShippingAddress && !$quoteShippingAddress->getCustomerId() && !$hasDefaultBillingAddress) {
            $quoteShippingAddress->setIsDefaultBilling(true);
        }

        $this->customerManagement->validateAddresses($quote);
        $this->customerManagement->populateCustomerInfo($quote);
    }

    /**
     * Check permission for approval exemption.
     *
     * @return bool
     */
    private function isCurrentUserExemptFromApproval()
    {
        return $this->authorization->isAllowed('Magento_PurchaseOrder::autoapprove_purchase_order');
    }
}
