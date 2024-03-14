<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Helper;

abstract class GoToProductTypes extends BasicEnum
{
    /** @var string */
    public const GOTOWEBINAR  = 'webinar';

    /** @var string */
    public const GOTOMEETING  = 'meeting';

    /** @var string */
    public const GOTOTRAINING = 'training';

    /** @var string */
    public const GOTOASSIST   = 'assist';
}
