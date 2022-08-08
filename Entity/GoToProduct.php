<?php

namespace MauticPlugin\MauticGoToBundle\Entity;

use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

const STATUS_ACTIVE = 'active';
const STATUS_HIDDEN = 'hidden';

class GoToProduct implements \JsonSerializable
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="product_key", type="string")
     */
    protected $product_key;

    /**
     * @ORM\Column(name="recurrence_key", type="string")
     */
    protected $recurrence_key;

    /**
     * @ORM\Column(name="organizer_key", type="string")
     */
    protected $organizer_key;
    /**
     * @ORM\Column(name="product", type="string", length=20)
     */
    protected $product;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @ORM\Column(name="description", type="text")
     */
    protected $description;

    /**
     * @ORM\Column(name = "date", type ="datetime")
     */
    protected $date;
    /**
     * @param ClassMetadata $metadata
     */

    /**
     * @ORM\Column(name="author", type="text")
     */
    protected $author;

    /**
     * @ORM\Column(name="language", type="text")
     */
    protected $language;

    /**
     * @ORM\Column(name="duration", type="text")
     */
    protected $duration;

    /**
     * @ORM\Column(name="status", type="text")
     */
    protected $status;

    public static function loadMetadata(ClassMetadata $metadata)
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
    public function setProductKey($product_key)
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
    public function setOrganizerKey($organizer_key)
    {
        $this->organizer_key = $organizer_key;
    }

    /**
     * @return mixed
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param mixed $product
     */
    public function setProduct($product)
    {
        $this->product = $product;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getRecurrenceKey()
    {
        return $this->recurrence_key;
    }

    /**
     * @param mixed $recurrence_key
     */
    public function setRecurrenceKey($recurrence_key)
    {
        $this->recurrence_key = $recurrence_key;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param mixed $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return mixed
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param mixed $author
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    }

    /**
     * @return mixed
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param mixed $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    /**
     * @return mixed
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param mixed $duration
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }
}
