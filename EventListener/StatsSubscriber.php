<?php

namespace MauticPlugin\LeuchtfeuerGoToBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\EventListener\CommonStatsSubscriber;

/**
 * Class StatsSubscriber.
 */
class StatsSubscriber extends CommonStatsSubscriber
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * StatsSubscriber constructor.
     */
    public function __construct(EntityManager $em)
    {
        $this->addContactRestrictedRepositories(
            $em,
            [
                'LeuchtfeuerGoToBundle:GoToEvent',
            ]
        );
    }
}
