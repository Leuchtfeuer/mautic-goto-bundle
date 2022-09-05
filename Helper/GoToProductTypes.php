<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticGoToBundle\Helper;

abstract class GoToProductTypes extends BasicEnum
{
    public const GOTOWEBINAR  = 'webinar';
    public const GOTOMEETING  = 'meeting';
    public const GOTOTRAINING = 'training';
    public const GOTOASSIST   = 'assist';
}
