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
    /**
     * @var string
     */
    public const STARTED    = 'started';

    /**
     * @var string
     */
    public const REGISTERED = 'registered';

    /**
     * @var string
     */
    public const ATTENDED   = 'attended';
}
