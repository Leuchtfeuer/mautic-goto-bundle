<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class GoToEventUpdateEvent.
 */
class GoToEventUpdateEvent extends CommonEvent
{
    /**
     * GoToEventUpdateEvent constructor.
     */
    public function __construct(
        private string $product,
        private string $eventName,
        private string $eventDesc,
        private string $eventType,
        private Lead $lead
    ) {
    }

    public function getProduct(): string
    {
        return $this->product;
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }

    public function getEmail(): string
    {
        return $this->lead->getEmail();
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getEventDesc(): string
    {
        return $this->eventDesc;
    }

    public function getLead(): Lead
    {
        return $this->lead;
    }
}
