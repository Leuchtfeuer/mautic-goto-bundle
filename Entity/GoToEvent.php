<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticGoToBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Lead;

/**
 * @ORM\Table(name="plugin_citrix_events")
 * @ORM\Entity(repositoryClass="GoToEventRepository")
 */
class GoToEvent
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Lead
     */
    protected $contact;

    /**
     * @var GoToProduct
     */
    protected $citrixProduct;

    /**
     * @ORM\Column(name="event_type", type="string", length=50)
     */
    protected $eventType;

    /**
     * @ORM\Column(name="event_date", type="datetime")
     */
    protected $eventDate;

    /**
     * @ORM\Column(name="join_url", type="datetime")
     */
    protected $joinUrl;

    public function __construct()
    {
        $this->eventDate = new \DateTime();
        $this->eventType = 'undefined';
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata)
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
     * @return array
     */
    public function toArray()
    {
        return get_object_vars($this);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Lead
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @param Lead $contact
     */
    public function setContact($contact)
    {
        $this->contact = $contact;
    }

    /**
     * @return GoToProduct
     */
    public function getGoToProduct()
    {
        return $this->citrixProduct;
    }

    /**
     * @param GoToProduct $citrixProduct
     */
    public function setGoToProduct($citrixProduct)
    {
        $this->citrixProduct = $citrixProduct;
    }

    /**
     * @return \DateTime
     */
    public function getEventDate()
    {
        return $this->eventDate;
    }

    /**
     * @return $this
     */
    public function setEventDate(\DateTime $eventDate)
    {
        $this->eventDate = $eventDate;

        return $this;
    }

    /**
     * @return string
     */
    public function getEventType()
    {
        return $this->eventType;
    }

    /**
     * @param $eventType
     *
     * @return $this
     */
    public function setEventType($eventType)
    {
        $this->eventType = $eventType;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getJoinUrl()
    {
        return $this->joinUrl;
    }

    /**
     * @param mixed $joinUrl
     */
    public function setJoinUrl($joinUrl)
    {
        $this->joinUrl = $joinUrl;
    }
}
