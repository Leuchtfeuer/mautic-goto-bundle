<?php

namespace MauticPlugin\LeuchtfeuerGoToBundle\Integration;

/**
 * Class HubspotIntegration.
 */
class GotowebinarIntegration extends GoToAbstractIntegration
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
