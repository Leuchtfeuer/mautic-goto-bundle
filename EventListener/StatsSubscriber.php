<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\EventListener\CommonStatsSubscriber;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use MauticPlugin\LeuchtfeuerGoToBundle\Entity\GoToEvent;

/**
 * Class StatsSubscriber.
 */
class StatsSubscriber extends CommonStatsSubscriber
{
    public function __construct(CorePermissions $security, EntityManager $entityManager)
    {
        parent::__construct($security, $entityManager);

        $this->addContactRestrictedRepositories([GoToEvent::class]);
    }
}
