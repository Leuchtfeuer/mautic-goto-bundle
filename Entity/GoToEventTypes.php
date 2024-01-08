<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Entity;

use MauticPlugin\LeuchtfeuerGoToBundle\Helper\BasicEnum;

abstract class GoToEventTypes extends BasicEnum
{
    // Used for querying events
    public const STARTED    = 'started';
    public const REGISTERED = 'registered';
    public const ATTENDED   = 'attended';
}
