<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PurchaseOrder\Model;

use Magento\Framework\Phrase;
use Magento\PurchaseOrder\Api\Data\CommentInterface;
use Magento\PurchaseOrder\Api\Data\CommentInterfaceFactory;
use Magento\PurchaseOrder\Model\Comment\SearchProvider;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PurchaseOrder\Model\ResourceModel\Comment;
use Psr\Log\LoggerInterface;

/**
 * Purchase order comment repository class.
 */
class CommentRepository implements CommentRepositoryInterface
{
    /**
     * @var CommentInterface[]
     */
    private $instances = [];

    /**
     * @var Comment
     */
    private $commentResource;

    /**
     * @var \Magento\PurchaseOrder\Api\Data\CommentInterfaceFactory
     */
    private $commentFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\PurchaseOrder\Model\Comment\SearchProvider
     */
    private $searchProvider;

    /**
     * CommentRepository constructor.
     *
     * @param Comment $commentResource
     * @param CommentInterfaceFactory $commentFactory
     * @param LoggerInterface $logger
     * @param SearchProvider $searchProvider
     */
    public function __construct(
        Comment $commentResource,
        CommentInterfaceFactory $commentFactory,
        LoggerInterface $logger,
        SearchProvider $searchProvider
    ) {
        $this->commentResource = $commentResource;
        $this->commentFactory = $commentFactory;
        $this->logger = $logger;
        $this->searchProvider = $searchProvider;
    }

    /**
     * @inheritdoc
     */
    public function save(CommentInterface $comment)
    {
        try {
            $this->commentResource->saveCommentData($comment);
        } catch (\Exception $exception) {
            $this->logger->critical($exception);
            throw new CouldNotSaveException(__('There was an error saving comment.'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        if (!isset($this->instances[$id])) {
            /** @var CommentInterface $comment */
            $comment = $this->commentFactory->create();
            $comment->load($id);
            if (!$comment->getId()) {
                throw new NoSuchEntityException(
                    new Phrase(
                        'No such entity with %fieldName = %fieldValue',
                        [
                            'fieldName' => 'id',
                            'fieldValue' => $id
                        ]
                    )
                );
            }
            $this->instances[$id] = $comment;
        }
        return $this->instances[$id];
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        return $this->searchProvider->getList($searchCriteria);
    }
}
