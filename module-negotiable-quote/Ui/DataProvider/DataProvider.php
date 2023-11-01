<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\NegotiableQuote\Ui\DataProvider;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Api\AuthorizationInterface;
use Magento\Company\Model\Company\Structure;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Reporting;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\NegotiableQuote\Model\NegotiableQuote;
use Magento\NegotiableQuote\Model\NegotiableQuoteRepository;
use Magento\NegotiableQuote\Model\Quote\Address;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class to provide Negotiable quote list
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    /**
     * @var Address
     */
    private $negotiableQuoteAddress;

    /**
     * @var NegotiableQuoteRepository
     */
    private $negotiableQuoteRepository;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Structure
     */
    private $structure;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param Reporting $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param NegotiableQuoteRepository $negotiableQuoteRepository
     * @param UserContextInterface $userContext
     * @param Address $negotiableQuoteAddress
     * @param StoreManagerInterface $storeManager
     * @param Structure $structure
     * @param AuthorizationInterface $authorization
     * @param array $meta
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        Reporting $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        NegotiableQuoteRepository $negotiableQuoteRepository,
        UserContextInterface $userContext,
        Address $negotiableQuoteAddress,
        StoreManagerInterface $storeManager,
        Structure $structure,
        AuthorizationInterface $authorization,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
        $this->negotiableQuoteRepository = $negotiableQuoteRepository;
        $this->userContext = $userContext;
        $this->negotiableQuoteAddress = $negotiableQuoteAddress;
        $this->storeManager = $storeManager;
        $this->structure = $structure;
        $this->request = $request;
        $this->authorization = $authorization;
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        /**
         * Return empty data in the main request.
         * The quote list will be loaded by custom ajax request.
         * It prevents double loading of the quote list
         */
        if ($this->request->getParam('namespace') === null) {
            return $this->formatEmptyOutput();
        }

        return $this->formatOutput($this->getSearchResult());
    }

    /**
     * Returns Search result
     *
     * @return SearchResultsInterface
     */
    public function getSearchResult()
    {
        $this->addOrder('entity_id', 'DESC');
        $customerId = $this->getCustomerId();
        $allTeamIds = [];
        if ($this->authorization->isAllowed('Magento_NegotiableQuote::view_quotes_sub')) {
            $allTeamIds = $this->structure->getAllowedChildrenIds($customerId);
        }
        $allTeamIds[] = $customerId;
        $filter = $this->filterBuilder
            ->setField('main_table.customer_id')
            ->setConditionType('in')
            ->setValue(array_unique($allTeamIds))
            ->create();
        $this->searchCriteriaBuilder->addFilter($filter);
        $filter = $this->filterBuilder
            ->setField('store_id')
            ->setConditionType('in')
            ->setValue($this->storeManager->getStore()->getWebsite()->getStoreIds())
            ->create();
        $this->searchCriteriaBuilder->addFilter($filter);
        $this->searchCriteria = $this->searchCriteriaBuilder->create();
        $this->searchCriteria->setRequestName($this->name);

        return $this->negotiableQuoteRepository->getList($this->getSearchCriteria(), true);
    }

    /**
     * Retrieve customer id
     *
     * @return int|null
     */
    private function getCustomerId()
    {
        return $this->userContext->getUserId() ? : null;
    }

    /**
     * Get formatted output result
     *
     * @param SearchResultsInterface $searchResult
     * @return array
     */
    private function formatOutput(SearchResultsInterface $searchResult)
    {
        $arrItems = [];
        $arrItems['totalRecords'] = $searchResult->getTotalCount();

        $arrItems['items'] = [];
        foreach ($searchResult->getItems() as $item) {
            $this->negotiableQuoteAddress->updateQuoteShippingAddressDraft($item->getId());
            $itemData = [];
            foreach ($item->getData() as $key => $value) {
                $itemData[$key] = $value;
            }
            $itemData = $this->addExtensionAttributes($item, $itemData);
            $arrItems['items'][] = $itemData;
        }
        return $arrItems;
    }

    /**
     * Add extension attributes to the item
     *
     * @param ExtensibleDataInterface $item
     * @param array $itemData
     * @return array
     */
    private function addExtensionAttributes(ExtensibleDataInterface $item, $itemData = [])
    {
        $extensionAttributes = $item->getExtensionAttributes();
        if (!is_object($extensionAttributes)) {
            return $itemData;
        }
        /** @var NegotiableQuote $negotiableQuote */
        $negotiableQuote = $extensionAttributes->getNegotiableQuote();
        if (!is_object($negotiableQuote)) {
            return $itemData;
        }

        foreach ($negotiableQuote->getData() as $key => $value) {
            $itemData[$key] = $value;
        }

        return $itemData;
    }

    /**
     * Get formatted output with empty result
     *
     * @return array
     */
    private function formatEmptyOutput(): array
    {
        return [
            'totalRecords' => 0,
            'items' => [],
        ];
    }
}
