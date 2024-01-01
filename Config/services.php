<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use MauticPlugin\LeuchtfeuerGoToBundle\Integration\GoToAbstractIntegration;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
    ];

    $services->load('MauticPlugin\\LeuchtfeuerGoToBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('MauticPlugin\\LeuchtfeuerGoToBundle\\Entity\\', '../Entity/*Repository.php');

    $services->set(\MauticPlugin\LeuchtfeuerGoToBundle\Api\GoToApi::class)
        ->args([\Symfony\Component\DependencyInjection\Loader\Configurator\inline_service(GoToAbstractIntegration::class)]);

    $services->set(\MauticPlugin\LeuchtfeuerGoToBundle\Api\GotoassistApi::class)
        ->args([\Symfony\Component\DependencyInjection\Loader\Configurator\inline_service(GoToAbstractIntegration::class)]);

    $services->set(\MauticPlugin\LeuchtfeuerGoToBundle\Api\GotomeetingApi::class)
        ->args([\Symfony\Component\DependencyInjection\Loader\Configurator\inline_service(GoToAbstractIntegration::class)]);

    $services->set(\MauticPlugin\LeuchtfeuerGoToBundle\Api\GototrainingApi::class)
        ->args([\Symfony\Component\DependencyInjection\Loader\Configurator\inline_service(GoToAbstractIntegration::class)]);

    $services->set(\MauticPlugin\LeuchtfeuerGoToBundle\Api\GotowebinarApi::class)
        ->args([\Symfony\Component\DependencyInjection\Loader\Configurator\inline_service(GoToAbstractIntegration::class)]);

//    $services->alias('mautic.helper.integration', \Mautic\PluginBundle\Helper\IntegrationHelper::class);

//    $services->alias('leuchtfeuer.goto.abstract.integration', GoToAbstractIntegration::class);
//    $services->set(\MauticPlugin\LeuchtfeuerGoToBundle\Integration\GotoassistIntegration::class)
//        ->parent(GoToAbstractIntegration::class);
//
//    $services->set(\MauticPlugin\LeuchtfeuerGoToBundle\Integration\GotomeetingIntegration::class)
//        ->parent(GoToAbstractIntegration::class);
//
//    $services->set(\MauticPlugin\LeuchtfeuerGoToBundle\Integration\GototrainingIntegration::class)
//        ->parent(GoToAbstractIntegration::class);
//
//    $services->set(\MauticPlugin\LeuchtfeuerGoToBundle\Integration\GotowebinarIntegration::class)
//        ->parent(GoToAbstractIntegration::class);
};
