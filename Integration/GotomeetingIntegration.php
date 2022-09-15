<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticGoToBundle\Integration;

/**
 * Class HubspotIntegration.
 */
class GotomeetingIntegration extends GoToAbstractIntegration
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Gotomeeting';
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'GoToMeeting';
    }
}
