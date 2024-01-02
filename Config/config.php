<?php

declare(strict_types=1);

use MauticPlugin\LeuchtfeuerGoToBundle\EventListener\CampaignSubscriber;
use MauticPlugin\LeuchtfeuerGoToBundle\EventListener\EmailSubscriber;
use MauticPlugin\LeuchtfeuerGoToBundle\EventListener\FormSubscriber;
use MauticPlugin\LeuchtfeuerGoToBundle\EventListener\IntegrationRequestSubscriber;
use MauticPlugin\LeuchtfeuerGoToBundle\EventListener\LeadSubscriber;
use MauticPlugin\LeuchtfeuerGoToBundle\EventListener\StatsSubscriber;
use MauticPlugin\LeuchtfeuerGoToBundle\Form\Type\GoToActionType;
use MauticPlugin\LeuchtfeuerGoToBundle\Form\Type\GoToCampaignActionType;
use MauticPlugin\LeuchtfeuerGoToBundle\Form\Type\GoToCampaignEventType;
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
                'controller' => 'LeuchtfeuerGoToBundle:Public:proxy',
            ],
            'mautic_citrix_sessionchanged' => [
                'path'       => '/citrix/sessionChanged',
                'controller' => 'LeuchtfeuerGoToBundle:Public:sessionChanged',
            ],
        ],
    ],
    'services' => [
        'events' => [
            'mautic.citrix.formbundle.subscriber' => [
                'class'     => FormSubscriber::class,
                'arguments' => [
                    'mautic.citrix.model.citrix',
                    'mautic.form.model.form',
                    'mautic.form.model.submission',
                    'translator',
                    'doctrine.orm.entity_manager',
                ],
                'methodCalls' => [
                    'setEmailModel' => ['mautic.email.model.email'],
                ],
            ],
            'mautic.citrix.leadbundle.subscriber' => [
                'class'     => LeadSubscriber::class,
                'arguments' => [
                    'mautic.citrix.model.citrix',
                    'doctrine.orm.entity_manager',
                    'translator',
                ],
            ],
            'mautic.citrix.campaignbundle.subscriber' => [
                'class'     => CampaignSubscriber::class,
                'arguments' => [
                    'mautic.citrix.model.citrix',
                    'doctrine.orm.entity_manager',
                    'translator',
                ],
                'methodCalls' => [
                    'setEmailModel' => ['mautic.email.model.email'],
                ],
            ],
            'mautic.citrix.emailbundle.subscriber' => [
                'class'     => EmailSubscriber::class,
                'arguments' => [
                    'mautic.citrix.model.citrix',
                    'translator',
                    'twig',
                    'event_dispatcher',
                ],
            ],
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
            'mautic.form.type.citrix.submitaction' => [
                'class'     => GoToActionType::class,
                'alias'     => 'citrix_submit_action',
                'arguments' => [
                    'mautic.form.model.field',
                    'mautic.citrix.model.citrix',
                ],
            ],
            'mautic.form.type.citrix.campaignevent' => [
                'class'     => GoToCampaignEventType::class,
                'alias'     => 'citrix_campaign_event',
                'arguments' => [
                    'mautic.citrix.model.citrix',
                    'translator',
                ],
            ],
            'mautic.form.type.citrix.campaignaction' => [
                'class'     => GoToCampaignActionType::class,
                'alias'     => 'citrix_campaign_action',
                'arguments' => [
                    'mautic.citrix.model.citrix',
                    'translator',
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
