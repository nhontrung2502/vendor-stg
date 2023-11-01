<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Magento\NegotiableQuote\Setup\Patch\Data;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\DB\Query\Generator;
use Psr\Log\LoggerInterface;

/**
 * Class Clean Up Data Removes unused data
 */
class WishlistDataCleanUp implements DataPatchInterface
{
    /**
     * Batch size for query
     */
    private const BATCH_SIZE = 1000;

    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var Generator
     */
    private $queryGenerator;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * RemoveData constructor.
     * @param Json $json
     * @param Generator $queryGenerator
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param LoggerInterface $logger
     */
    public function __construct(
        Json $json,
        Generator $queryGenerator,
        ModuleDataSetupInterface $moduleDataSetup,
        LoggerInterface $logger
    ) {
        $this->json = $json;
        $this->queryGenerator = $queryGenerator;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function apply()
    {
        try {
            $this->cleanNegotiableQuoteTable();
        } catch (\Throwable $e) {
            $this->logger->warning(
                'NegotiableQuote module WishlistDataCleanUp patch experienced an error and could not be completed.'
                . ' Please submit a support ticket or email us at security@magento.com.'
            );

            return $this;
        }

        return $this;
    }

    /**
     * Remove login data from negotiable_quote table.
     *
     * @throws LocalizedException
     */
    private function cleanNegotiableQuoteTable()
    {
        $tableName = $this->moduleDataSetup->getTable('negotiable_quote', 'checkout');
        $select = $this->moduleDataSetup
            ->getConnection('checkout')
            ->select()
            ->from(
                $tableName,
                ['quote_id', 'snapshot']
            )
            ->where(
                'snapshot LIKE ?',
                '%login%'
            );
        $iterator = $this->queryGenerator->generate('quote_id', $select, self::BATCH_SIZE);
        $rowErrorFlag = false;
        foreach ($iterator as $selectByRange) {
            $quoteRows = $this->moduleDataSetup->getConnection('checkout')->fetchAll($selectByRange);
            foreach ($quoteRows as $quoteRow) {
                try {
                    $rowValue = $this->removeLogin($quoteRow['snapshot']);
                    $this->moduleDataSetup->getConnection('checkout')->update(
                        $tableName,
                        ['snapshot' => $rowValue],
                        ['quote_id = ?' => $quoteRow['quote_id']]
                    );
                } catch (\Throwable $e) {
                    $rowErrorFlag = true;
                    continue;
                }
            }
        }
        if ($rowErrorFlag) {
            $this->logger->warning(
                'Data clean up could not be completed due to unexpected data format in the table "'
                . $tableName
                . '". Please submit a support ticket or email us at security@magento.com.'
            );
        }
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [
            ConvertValuesFromSerializeToJson::class
        ];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * Removes login key pair value from snapshots
     *
     * @param string $snapshot
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function removeLogin(string $snapshot)
    {
        $rowValue = $this->json->unserialize($snapshot);
        if (is_array($rowValue)
            && is_array($rowValue['items'])
        ) {
            foreach ($rowValue['items'] as $itemIndex => $item) {
                if (is_array($item['options'])
                ) {
                    foreach ($item['options'] as $optionIndex => $option) {
                        $optionValue = $this->json->unserialize($option['value']);
                        if (is_array($optionValue)
                            && array_key_exists('login', $optionValue)
                        ) {
                            unset($optionValue['login']);
                            $optionValue = $this->json->serialize($optionValue);
                            $rowValue['items'][$itemIndex]['options'][$optionIndex]['value'] = $optionValue;
                        }
                    }
                }
            }
        }
        return $this->json->serialize($rowValue);
    }
}
