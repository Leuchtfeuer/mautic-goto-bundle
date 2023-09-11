<?php

namespace MauticPlugin\LeuchtfeuerGoToBundle\Integration;

/**
 * Class HubspotIntegration.
 */
class GototrainingIntegration extends GoToAbstractIntegration
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Gototraining';
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'GoToTraining';
    }
}
