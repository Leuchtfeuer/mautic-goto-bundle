<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\LeuchtfeuerGoToBundle\Entity\GoToEvent;
use MauticPlugin\LeuchtfeuerGoToBundle\Entity\GoToProduct;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LoadPageData.
 */
class LoadCitrixData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    private ContainerInterface $container;

    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager): void
    {
        $em    = $this->container->get('doctrine')->getManager();
        $today = new \DateTime();
        $email = 'joe.o\'connor@domain.com';

        // create a new lead
        $lead = new Lead();
        $lead->setDateAdded($today);
        $lead->setEmail($email);
        $lead->checkAttributionDate();

        $em->persist($lead);
        $em->flush();

        $this->setReference('lead-citrix', $lead);

        // create a product

        $product = new GoToProduct();
        $product->setProductKey('product-key-1');
        $product->setRecurrenceKey('recurrence-key-1');
        $product->setOrganizerKey('org-key');
        $product->setProduct('webinar');
        $product->setName('Webinar 01');
        $product->setDate(new \DateTime());
        $product->setDescription('Description');
        $product->setAuthor('Org');
        $product->setLanguage('en_US');
        $product->setDuration('3600');
        $product->setStatus('active');

        $em->persist($product);
        $em->flush();

        $this->setReference('citrix-product-1', $product);

        // create event
        $event = new GoToEvent();
        $event->setContact($lead);
        $event->setEventDate($today);
        $event->setGoToProduct($product);
        $event->setEventType('registered');

        $em->persist($event);
        $em->flush();

        $this->setReference('citrix-event', $event);
    }

    public function getOrder(): int
    {
        return 10;
    }
}
