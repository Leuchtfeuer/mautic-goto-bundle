<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\LeuchtfeuerGoToBundle\Integration;

/**
 * Class HubspotIntegration.
 */
class GotoassistIntegration extends GoToAbstractIntegration
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Gotoassist';
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'GoToAssist';
    }
}
