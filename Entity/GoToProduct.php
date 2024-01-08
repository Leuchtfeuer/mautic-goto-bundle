<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Entity;

use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

const STATUS_ACTIVE = 'active';
const STATUS_HIDDEN = 'hidden';

class GoToProduct implements \JsonSerializable
{
    protected int $id;
    protected string $product_key;
    protected ?string $recurrence_key;
    protected string $organizer_key;
    protected string $product;
    protected string $name;
    protected string $description;
    protected \DateTime $date;
    protected ?string $author;
    protected string $language;
    protected string $duration;
    protected string $status;

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('plugin_goto_products')
            ->setCustomRepositoryClass(GoToProductRepository::class);
        $builder->addId();
        $builder->addNamedField('product_key', 'string', 'product_key');
        $builder->addNamedField('recurrence_key', 'string', 'recurrence_key', true);
        $builder->addNamedField('organizer_key', 'string', 'organizer_key', true);
        $builder->addNamedField('product', 'string', 'product');
        $builder->addNamedField('name', 'string', 'name');
        $builder->addNamedField('date', 'datetime', 'date');
        $builder->addNamedField('description', 'text', 'description', true);
        $builder->addNamedField('author', 'text', 'author', true);
        $builder->addNamedField('language', 'text', 'language', true);
        $builder->addNamedField('duration', 'text', 'duration', true);
        $builder->addNamedField('status', 'text', 'status', true);
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getProductKey()
    {
        return $this->product_key;
    }

    /**
     * @param mixed $product_key
     */
    public function setProductKey($product_key): void
    {
        $this->product_key = $product_key;
    }

    /**
     * @return mixed
     */
    public function getOrganizerKey()
    {
        return $this->organizer_key;
    }

    /**
     * @param mixed $organizer_key
     */
    public function setOrganizerKey($organizer_key): void
    {
        $this->organizer_key = $organizer_key;
    }

    public function getProduct(): string
    {
        return $this->product;
    }

    public function setProduct(string $product): void
    {
        $this->product = $product;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getRecurrenceKey(): ?string
    {
        return $this->recurrence_key;
    }

    public function setRecurrenceKey(?string $recurrence_key): void
    {
        $this->recurrence_key = $recurrence_key;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): void
    {
        $this->date = $date;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): void
    {
        $this->author = $author;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    public function getDuration(): string
    {
        return $this->duration;
    }

    public function setDuration(string $duration): void
    {
        $this->duration = $duration;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }
}
