<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\LeuchtfeuerGoToBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\LeuchtfeuerGoToBundle\Entity\GoToEvent;
use MauticPlugin\LeuchtfeuerGoToBundle\Entity\GoToProduct;

/**
 * Class LoadPageData.
 */
class LoadCitrixData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $today = new \DateTime();
        $email = 'joe.o\'connor@domain.com';

        // create a new lead
        $lead = new Lead();
        $lead->setDateAdded($today);
        $lead->setEmail($email);
        $lead->checkAttributionDate();

        $manager->persist($lead);
        $manager->flush();

        $this->setReference('lead-citrix', $lead);

        $product = new GoToProduct();
        $product->setName('Sample Webinar');
        $product->setDate($today->add(new \DateInterval('P1D')));
        $product->setDuration(3600);
        $product->setProductKey('1234567');
        $product->setRecurrenceKey('7654321');
        $product->setOrganizerKey('12123434');
        $product->setProduct('webinar');
        $product->setStatus('active');

        $manager->persist($product);
        $manager->flush();

        $this->setReference('citrix-product', $product);


        // create event
        $event = new GoToEvent();
        $event->setContact($lead);
        $event->setEventDate($today);
        $event->setGoToProduct($product);
        $event->setContact($lead);
        $event->setEventType('registered');
        $event->setJoinUrl('sample-webinar_#0000');
//        $event->setEventDesc('Sample Webinar');

        $manager->persist($event);
        $manager->flush();

        $this->setReference('citrix-event', $event);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 10;
    }
}
