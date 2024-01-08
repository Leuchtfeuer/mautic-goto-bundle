<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle;

use Mautic\PluginBundle\Bundle\PluginBundleBase;

/**
 * Class LeuchtfeuerGoToBundle.
 */
class LeuchtfeuerGoToBundle extends PluginBundleBase
{
//    /**
//     * Called by PluginController::reloadAction when adding a new plugin that's not already installed.
//     *
//     * @param null $metadata
//     * @param null $installedSchema
//     *
//     * @throws \Doctrine\DBAL\ConnectionException
//     * @throws Exception
//     */
//    public static function onPluginInstall(Plugin $plugin, MauticFactory $factory, $metadata = null, $installedSchema = null): void
//    {
//        $db             = $factory->getDatabase();
//        $queries        = [];
//
//        $queries[] = 'DELETE FROM '.MAUTIC_TABLE_PREFIX.'plugins WHERE bundle = "MauticCitrixBundle"';
//        $queries[] = 'DELETE FROM '.MAUTIC_TABLE_PREFIX.'plugin_integration_settings WHERE name LIKE "goto%"';
//
//        if (!empty($queries)) {
//            $db->beginTransaction();
//            try {
//                foreach ($queries as $q) {
//                    $db->query($q);
//                }
//
//                $db->commit();
//            } catch (Exception $exception) {
//                $db->rollback();
//
//                $this->goToHelper->log($exception->getMessage(), LogLevel::NOTICE);
//            }
//        }
//
//        if (null !== $metadata) {
//            try {
//                self::installPluginSchema($metadata, $factory);
//            } catch (TableExistsException $tableExistsException) {
//                $this->goToHelper->log($tableExistsException->getMessage(), LogLevel::NOTICE);
//            }
//        }
//    }
}
