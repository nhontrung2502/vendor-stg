<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NegotiableQuote\Model\Attachment;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Math\Random;
use Magento\NegotiableQuote\Model\Config as NegotiableQuoteConfig;

/**
 * Class for uploading comment attachments.
 */
class Uploader extends \Magento\Framework\File\Uploader
{
    /**
     * @var NegotiableQuoteConfig
     */
    protected $negotiableQuoteConfig;

    /**
     * This number is used to convert Mbs in bytes.
     *
     *
     * @var int
     */
    private $defaultSizeMultiplier = 1048576;

    /**
     * Default file name length.
     *
     * @var int
     */
    private $defaultNameLength = 20;

    /**
     * @param NegotiableQuoteConfig $negotiableQuoteConfig
     * @throws \Exception
     */
    public function __construct(
        NegotiableQuoteConfig $negotiableQuoteConfig
    ) {
        $this->negotiableQuoteConfig = $negotiableQuoteConfig;
    }

    /**
     * Validate size of file.
     *
     * @return bool
     */
    public function validateSize()
    {
        return isset($this->_file['size'])
        && $this->_file['size'] < $this->negotiableQuoteConfig->getMaxFileSize() * $this->defaultSizeMultiplier;
    }

    /**
     * Validate name length of file.
     *
     * @return bool
     */
    public function validateNameLength()
    {
        return mb_strlen($this->_file['name']) <= $this->defaultNameLength;
    }

    /**
     * Check is file has allowed extension.
     *
     * @inheritdoc
     */
    public function checkAllowedExtension($extension)
    {
        if (empty($this->_allowedExtensions)) {
            $configData = $this->negotiableQuoteConfig->getAllowedExtensions();
            $allowedExtensions = $configData ? explode(',', $configData) : [];
            $this->_allowedExtensions = $allowedExtensions;
        }
        return parent::checkAllowedExtension($extension);
    }

    /**
     * @inheritDoc
     */
    public static function getNewFileName($destinationFile)
    {
        /** @var Random $random */
        $random = ObjectManager::getInstance()->get(Random::class);

        return $random->getRandomString(32);
    }

    /**
     * Explicitly set the file attributes instead of setting it via constructor
     *
     * @param array $fileAttributes
     * @return void
     * @throws \Exception
     */
    public function processFileAttributes($fileAttributes)
    {
        $this->_file = $fileAttributes;
        if (!file_exists($this->_file['tmp_name'])) {
            $code = empty($this->_file['tmp_name']) ? self::TMP_NAME_EMPTY : 0;

            // phpcs:ignore Magento2.Exceptions.DirectThrow.FoundDirectThrow
            throw new \Exception('File was not processed correctly.', $code);
        } else {
            $this->_fileExists = true;
        }
    }
}
