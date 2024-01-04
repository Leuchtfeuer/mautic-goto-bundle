<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Integration;

class GototrainingIntegration extends GoToAbstractIntegration
{
    public function getName(): string
    {
        return 'Gototraining';
    }

    public function getDisplayName(): string
    {
        return 'GoToTraining';
    }
}
