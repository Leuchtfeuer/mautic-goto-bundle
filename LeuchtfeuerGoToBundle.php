<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle;

use Doctrine\DBAL\Exception\TableExistsException;
use Exception;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\PluginBundle\Bundle\PluginBundleBase;
use Mautic\PluginBundle\Entity\Plugin;
use MauticPlugin\LeuchtfeuerGoToBundle\Helper\GoToHelper;
use Psr\Log\LogLevel;

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

    public function boot(): void
    {
        parent::boot();

//        if (
//            $this->container->getParameter('mautic.helper.integration') &&
//            $this->container->getParameter('monolog.logger.mautic')
//        ) {
//            dump('here in boot');
//            $this->goToHelper->init($this->container->get('mautic.helper.integration'), $this->container->get('monolog.logger.mautic'));
//        }

    }
}
