<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Integration;

class GotowebinarIntegration extends GoToAbstractIntegration
{
    public function getName(): string
    {
        return 'Gotowebinar';
    }

    public function getDisplayName(): string
    {
        return 'GoToWebinar';
    }
}
