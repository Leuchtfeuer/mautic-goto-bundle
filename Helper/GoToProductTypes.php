<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
