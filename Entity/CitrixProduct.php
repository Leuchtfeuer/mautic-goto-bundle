<?php


namespace MauticPlugin\MauticCitrixBundle\Entity;


use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use MauticPlugin\MauticCitrixBundle\Entity\CitrixProductRepository;


class CitrixProduct
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="$product_key", type="string", length=20)
     */
    protected $product_key;

    /**
     * @ORM\Column(name="$recurrence_key", type="string", length=20)
     */
    protected $recurrence_key;

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
    public static function loadMetadata(ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('plugin_citrix_products')
            ->setCustomRepositoryClass(CitrixProductRepository::class);
        $builder->addId();
        $builder->addNamedField('product_key', 'string', 'product_key');
        $builder->addNamedField('recurrence_key', 'string', 'recurrence_key', true);
        $builder->addNamedField('product', 'string', 'product');
        $builder->addNamedField('name', 'string', 'name');
        $builder->addNamedField('date', 'datetime', 'date');
        $builder->addNamedField('description', 'text', 'description', true);
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
     * @return mixed
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





}