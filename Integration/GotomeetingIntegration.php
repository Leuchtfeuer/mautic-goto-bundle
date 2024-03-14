<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Integration;

class GotomeetingIntegration extends GoToAbstractIntegration
{
    public function getName(): string
    {
        return 'Gotomeeting';
    }

    public function getDisplayName(): string
    {
        return 'GoToMeeting';
    }
}
