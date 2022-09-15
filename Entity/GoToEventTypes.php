<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticGoToBundle\Entity;

use MauticPlugin\MauticGoToBundle\Helper\BasicEnum;

abstract class GoToEventTypes extends BasicEnum
{
    // Used for querying events
    const STARTED    = 'started';
    const REGISTERED = 'registered';
    const ATTENDED   = 'attended';
}
