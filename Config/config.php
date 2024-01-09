<?php

declare(strict_types=1);

use MauticPlugin\LeuchtfeuerGoToBundle\EventListener\IntegrationRequestSubscriber;
use MauticPlugin\LeuchtfeuerGoToBundle\EventListener\StatsSubscriber;
use MauticPlugin\LeuchtfeuerGoToBundle\Form\Type\GoToListType;
use MauticPlugin\LeuchtfeuerGoToBundle\Integration\GotoassistIntegration;
use MauticPlugin\LeuchtfeuerGoToBundle\Integration\GotomeetingIntegration;
use MauticPlugin\LeuchtfeuerGoToBundle\Integration\GototrainingIntegration;
use MauticPlugin\LeuchtfeuerGoToBundle\Integration\GotowebinarIntegration;

return [
    'name'        => 'GoTo Integration by Leuchtfeuer',
    'description' => 'Enables integration with Mautic supported GoTo collaboration products.',
    'version'     => '4.0.0',
    'author'      => 'Leuchtfeuer Digital Marketing GmbH',
    'routes'      => [
        'public' => [
            'mautic_citrix_proxy' => [
                'path'       => '/citrix/proxy',
                'controller' => 'MauticPlugin\LeuchtfeuerGoToBundle\Controller\PublicController::proxyAction',
            ],
            'mautic_citrix_sessionchanged' => [
                'path'       => '/citrix/sessionChanged',
                'controller' => 'MauticPlugin\LeuchtfeuerGoToBundle\Controller\PublicController::sessionChangedAction',
            ],
        ],
    ],
    'services' => [
        'events' => [
            'mautic.citrix.stats.subscriber' => [
                'class'     => StatsSubscriber::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.citrix.integration.request' => [
                'class'     => IntegrationRequestSubscriber::class,
                'arguments' => [],
            ],
        ],
        'forms' => [
            'mautic.form.type.fieldslist.citrixlist' => [
                'class'     => GoToListType::class,
                'alias'     => 'citrix_list',
                'arguments' => [
                    'mautic.citrix.model.citrix',
                ],
            ],
        ],
        'integrations' => [
            'mautic.integration.gotoassist' => [
                'class'     => GotoassistIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'session',
                    'request_stack',
                    'router',
                    'translator',
                    'logger',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                ],
            ],
            'mautic.integration.gotomeeting' => [
                'class'     => GotomeetingIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'session',
                    'request_stack',
                    'router',
                    'translator',
                    'logger',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                ],
            ],
            'mautic.integration.gototraining' => [
                'class'     => GototrainingIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'session',
                    'request_stack',
                    'router',
                    'translator',
                    'logger',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                ],
            ],
            'mautic.integration.gotowebinar' => [
                'class'     => GotowebinarIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'session',
                    'request_stack',
                    'router',
                    'translator',
                    'logger',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                ],
            ],
        ],
    ],
];
