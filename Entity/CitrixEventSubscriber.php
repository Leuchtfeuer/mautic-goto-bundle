<?php


namespace MauticPlugin\MauticCitrixBundle\Entity;


use Doctrine\ORM\Mapping\ClassMetadata as ORMClassMetadata;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticCitrixBundle\Entity\CitrixEvent;

class CitrixEventSubscriber
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
     * @var CitrixEvent
     */
    protected $citrix_event;

    /**
     * @var string
     */
    protected $join_url_tail;

    /**
     * @param ORMClassMetadata $metadata
     */
    public static function loadMetadata(ORMClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('plugin_citrix_events_subscriber')
            ->setCustomRepositoryClass(CitrixEventSubscriberRepository::class);
        $builder->addId();
        $builder->createManyToOne('citrix_event', CitrixEvent::class)
            ->addJoinColumn('citrix_event_id', 'id', true, false, 'SET NULL')
            ->build();
        $builder->addNamedField('join_url_tail', 'string', 'join_url_tail');
        $builder->addContact();
    }

    /**
     * @return mixed
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
    public function setLead($contact)
    {
        $this->lead = $contact;
    }

    /**
     * @return CitrixEvent
     */
    public function getCitrixEvent()
    {
        return $this->citrix_event;
    }

    /**
     * @param CitrixEvent $citrix_event
     */
    public function setCitrixEvent($citrix_event)
    {
        $this->citrix_event = $citrix_event;
    }

    /**
     * @return string
     */
    public function getJoinUrlTail()
    {
        return $this->join_url_tail;
    }

    /**
     * @param string $join_url_tail
     */
    public function setJoinUrlTail($join_url_tail)
    {
        $this->join_url_tail = $join_url_tail;
    }



}