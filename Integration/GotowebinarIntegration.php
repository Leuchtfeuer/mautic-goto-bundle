<?php

/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle\Integration;

/**
 * Class HubspotIntegration.
 */
class GotowebinarIntegration extends CitrixAbstractIntegration
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Gotowebinar';
    }
    
    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'GoToWebinar';
    }

}
