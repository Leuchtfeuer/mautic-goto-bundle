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

use Doctrine\ORM\Tools\SchemaTool;
use Exception;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\PluginBundle\Bundle\PluginBundleBase;
use Mautic\PluginBundle\Entity\Plugin;
use MauticPlugin\MauticGoToBundle\Helper\GoToHelper;

/**
 * Class MauticGoToBundle.
 */
class MauticGoToBundle extends PluginBundleBase
{
    /**
     * Called by PluginController::reloadAction when adding a new plugin that's not already installed
     *
     * @param Plugin $plugin
     * @param MauticFactory $factory
     * @param null $metadata
     * @param null $installedSchema
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws Exception
     */

    public static function onPluginInstall(Plugin $plugin, MauticFactory $factory, $metadata = null, $installedSchema = null)
    {
        if ($metadata !== null) {
            self::installPluginSchema($metadata, $factory);
        }

        $db             = $factory->getDatabase();
        $queries        = array();

        $queries[] = 'DELETE FROM ' . MAUTIC_TABLE_PREFIX . 'plugins WHERE bundle = "MauticCitrixBundle"';
        $queries[] = 'DELETE FROM ' . MAUTIC_TABLE_PREFIX . 'plugin_integration_settings WHERE name LIKE "goto%"';
        $queries[] = 'TRUNCATE TABLE' . MAUTIC_TABLE_PREFIX . 'plugin_citrix_events';

        if (!empty($queries)) {

            $db->beginTransaction();
            try {
                foreach ($queries as $q) {
                    $db->query($q);
                }

                $db->commit();
            } catch (Exception $e) {
                $db->rollback();

                throw $e;
            }
        }
        if($installedSchema !== null){
            parent::updatePluginSchema($metadata, $installedSchema, $factory);
        }
    }

    /**
     * Called by PluginController::reloadAction when the plugin version does not match what's installed
     *
     * @param Plugin        $plugin
     * @param MauticFactory $factory
     * @param null          $metadata
     * @param Schema        $installedSchema
     *
     * @throws Exception
     */
    public static function onPluginUpdate(Plugin $plugin, MauticFactory $factory, $metadata = null, Schema $installedSchema = null)
    {

    }

    public function boot()
    {
        parent::boot();

        GoToHelper::init($this->container->get('mautic.helper.integration'), $this->container->get('monolog.logger.mautic'));
    }
}
