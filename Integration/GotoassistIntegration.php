<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Integration;

class GotoassistIntegration extends GoToAbstractIntegration
{
    public function getName(): string
    {
        return 'Gotoassist';
    }

    public function getDisplayName(): string
    {
        return 'GoToAssist';
    }
}
