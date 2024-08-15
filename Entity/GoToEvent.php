<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Lead;

class GoToEvent
{
    protected int $id;
    protected Lead $contact;
    protected ?GoToProduct $citrixProduct;
    protected string $eventType = 'undefined';
    protected \DateTime $eventDate;
    protected ?string $joinUrl;

    public function __construct()
    {
        $this->eventDate = new \DateTime();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('plugin_goto_events')
            ->setCustomRepositoryClass(GoToEventRepository::class);
        $builder->addId();
        $builder->createField('eventType', 'string')
            ->columnName('event_type')
            ->length(50)
            ->build();
        $builder->addNamedField('eventDate', 'datetime', 'event_date');
        $builder->addNamedField('joinUrl', 'string', 'join_url', true);
        $builder->addContact();
        $builder->createManyToOne('citrixProduct', GoToProduct::class)
            ->addJoinColumn('citrix_product_id', 'id', true, false, 'SET NULL')
            ->build();
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getContact(): Lead
    {
        return $this->contact;
    }

    public function setContact(Lead $contact): void
    {
        $this->contact = $contact;
    }

    public function getGoToProduct(): GoToProduct
    {
        return $this->citrixProduct;
    }

    public function setGoToProduct(GoToProduct $citrixProduct): void
    {
        $this->citrixProduct = $citrixProduct;
    }

    public function getEventDate(): \DateTime
    {
        return $this->eventDate;
    }

    public function setEventDate(\DateTime $eventDate): static
    {
        $this->eventDate = $eventDate;

        return $this;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getJoinUrl(): ?string
    {
        return $this->joinUrl;
    }

    public function setEventType(string $eventType): static
    {
        $this->eventType = $eventType;

        return $this;
    }

    public function setJoinUrl($joinUrl): void
    {
        $this->joinUrl = $joinUrl;
    }

    public function getCitrixProduct(): ?GoToProduct
    {
        return $this->citrixProduct;
    }

    public function setCitrixProduct(?GoToProduct $citrixProduct): static
    {
        $this->citrixProduct = $citrixProduct;

        return $this;
    }
}
