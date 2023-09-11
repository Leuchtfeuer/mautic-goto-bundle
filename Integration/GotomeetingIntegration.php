<?php

namespace MauticPlugin\LeuchtfeuerGoToBundle\Integration;

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
