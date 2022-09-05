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

use Doctrine\DBAL\Exception\TableExistsException;
use Exception;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\PluginBundle\Bundle\PluginBundleBase;
use Mautic\PluginBundle\Entity\Plugin;
use MauticPlugin\MauticGoToBundle\Helper\GoToHelper;
use Psr\Log\LogLevel;

/**
 * Class MauticGoToBundle.
 */
class MauticGoToBundle extends PluginBundleBase
{
    /**
     * Called by PluginController::reloadAction when adding a new plugin that's not already installed.
     *
     * @param null $metadata
     * @param null $installedSchema
     *
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws Exception
     */
    public static function onPluginInstall(Plugin $plugin, MauticFactory $factory, $metadata = null, $installedSchema = null)
    {
        $db             = $factory->getDatabase();
        $queries        = [];

        $queries[] = 'DELETE FROM '.MAUTIC_TABLE_PREFIX.'plugins WHERE bundle = "MauticCitrixBundle"';
        $queries[] = 'DELETE FROM '.MAUTIC_TABLE_PREFIX.'plugin_integration_settings WHERE name LIKE "goto%"';

        if (!empty($queries)) {
            $db->beginTransaction();
            try {
                foreach ($queries as $q) {
                    $db->query($q);
                }

                $db->commit();
            } catch (Exception $e) {
                $db->rollback();

                GoToHelper::log($e->getMessage(), LogLevel::NOTICE);
            }
        }

        if (null !== $metadata) {
            try {
                self::installPluginSchema($metadata, $factory);
            } catch (TableExistsException $e) {
                GoToHelper::log($e->getMessage(), LogLevel::NOTICE);
            }
        }
    }

    public function boot()
    {
        parent::boot();

        GoToHelper::init($this->container->get('mautic.helper.integration'), $this->container->get('monolog.logger.mautic'));
    }
}
