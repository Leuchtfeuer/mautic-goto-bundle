<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticGoToBundle\EventListener;

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
                'MauticGoToBundle:GoToEvent',
            ]
        );
    }
}
