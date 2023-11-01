<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrder\Model\Notification\Notifier;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Queue message wrapper.
 */
class QueueMessage
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * QueueMessage constructor.
     *
     * @param SerializerInterface $serializer
     * @param string|null $serializedData
     * @throws LocalizedException
     */
    public function __construct(
        SerializerInterface $serializer,
        string $serializedData = null
    ) {
        $this->serializer = $serializer;
        if (!empty($serializedData)) {
            $this->data = $this->serializer->unserialize($serializedData);
            $this->validateData();
        }
    }

    /**
     * Set subject identifier.
     *
     * @param int $subjectEntityId
     */
    public function setSubjectEntityId($subjectEntityId)
    {
        $this->data['subject_entity_id'] = $subjectEntityId;
    }

    /**
     * Set notification action class.
     *
     * @param string $actionClass
     */
    public function setActionClass($actionClass)
    {
        $this->data['action_class'] = $actionClass;
    }

    /**
     * Get subject identifier.
     *
     * @return int|null
     */
    public function getSubjectEntityId()
    {
        return isset($this->data['subject_entity_id']) ? $this->data['subject_entity_id'] : null;
    }

    /**
     * Get action class.
     *
     * @return string|null
     */
    public function getActionClass() : ?string
    {
        return isset($this->data['action_class']) ? $this->data['action_class'] : null;
    }

    /**
     * Get publication data for queue message.
     *
     * @return string
     * @throws LocalizedException
     */
    public function getPublicationData()
    {
        $this->validateData();
        return $this->serializer->serialize([
            'subject_entity_id' => $this->data['subject_entity_id'],
            'action_class' => $this->data['action_class'],
        ]);
    }

    /**
     * Validate notification queue message data.
     *
     * @throws LocalizedException
     */
    private function validateData()
    {
        if (!isset($this->data['subject_entity_id']) || !isset($this->data['action_class'])) {
            throw new LocalizedException(__('Notification Queue Message is not fulfilled properly'));
        }
    }
}
