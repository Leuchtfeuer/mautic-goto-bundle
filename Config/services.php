<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use MauticPlugin\LeuchtfeuerGoToBundle\EventListener\CampaignSubscriber;
use MauticPlugin\LeuchtfeuerGoToBundle\EventListener\EmailSubscriber;
use MauticPlugin\LeuchtfeuerGoToBundle\EventListener\FormSubscriber;
use MauticPlugin\LeuchtfeuerGoToBundle\Model\GoToModel;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

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

    $services->alias('mautic.citrix.model.citrix', GoToModel::class);

    $services->set(CampaignSubscriber::class)
        ->call('setEmailModel', [service('mautic.email.model.email')]);

    $services->set(FormSubscriber::class)
        ->call('setEmailModel', [service('mautic.email.model.email')]);
//    $services->alias('mautic.citrix.emailbundle.subscriber', EmailSubscriber::class)
//        ->call('setEmailModel', [service('mautic.email.model.email')]);
};
