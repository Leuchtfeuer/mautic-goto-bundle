<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticGoToBundle;

use Mautic\PluginBundle\Bundle\PluginBundleBase;
use MauticPlugin\MauticGoToBundle\Helper\GoToHelper;

/**
 * Class MauticGoToBundle.
 */
class MauticGoToBundle extends PluginBundleBase
{
    public function boot()
    {
        parent::boot();

        GoToHelper::init($this->container->get('mautic.helper.integration'), $this->container->get('monolog.logger.mautic'));
    }
}
