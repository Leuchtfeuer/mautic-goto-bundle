<?php

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
