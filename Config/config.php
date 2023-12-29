<?php

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
                'class'     => \MauticPlugin\LeuchtfeuerGoToBundle\EventListener\FormSubscriber::class,
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
                'class'     => \MauticPlugin\LeuchtfeuerGoToBundle\EventListener\LeadSubscriber::class,
                'arguments' => [
                    'mautic.citrix.model.citrix',
                    'doctrine.orm.entity_manager',
                    'translator',
                ],
            ],
            'mautic.citrix.campaignbundle.subscriber' => [
                'class'     => \MauticPlugin\LeuchtfeuerGoToBundle\EventListener\CampaignSubscriber::class,
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
                'class'     => \MauticPlugin\LeuchtfeuerGoToBundle\EventListener\EmailSubscriber::class,
                'arguments' => [
                    'mautic.citrix.model.citrix',
                    'translator',
                    'twig',
                    'event_dispatcher',
                ],
            ],
            'mautic.citrix.stats.subscriber' => [
                'class'     => \MauticPlugin\LeuchtfeuerGoToBundle\EventListener\StatsSubscriber::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.citrix.integration.request' => [
                'class'     => \MauticPlugin\LeuchtfeuerGoToBundle\EventListener\IntegrationRequestSubscriber::class,
                'arguments' => [],
            ],
        ],
        'forms' => [
            'mautic.form.type.fieldslist.citrixlist' => [
                'class'     => \MauticPlugin\LeuchtfeuerGoToBundle\Form\Type\GoToListType::class,
                'alias'     => 'citrix_list',
                'arguments' => [
                    'mautic.citrix.model.citrix',
                ],
            ],
            'mautic.form.type.citrix.submitaction' => [
                'class'     => \MauticPlugin\LeuchtfeuerGoToBundle\Form\Type\GoToActionType::class,
                'alias'     => 'citrix_submit_action',
                'arguments' => [
                    'mautic.form.model.field',
                    'mautic.citrix.model.citrix',
                ],
            ],
            'mautic.form.type.citrix.campaignevent' => [
                'class'     => \MauticPlugin\LeuchtfeuerGoToBundle\Form\Type\GoToCampaignEventType::class,
                'alias'     => 'citrix_campaign_event',
                'arguments' => [
                    'mautic.citrix.model.citrix',
                    'translator',
                ],
            ],
            'mautic.form.type.citrix.campaignaction' => [
                'class'     => \MauticPlugin\LeuchtfeuerGoToBundle\Form\Type\GoToCampaignActionType::class,
                'alias'     => 'citrix_campaign_action',
                'arguments' => [
                    'mautic.citrix.model.citrix',
                    'translator',
                ],
            ],
        ],
        'models' => [
            'mautic.citrix.model.citrix' => [
                'class'     => \MauticPlugin\LeuchtfeuerGoToBundle\Model\GoToModel::class,
                'arguments' => [
                    'mautic.lead.model.lead',
                    'mautic.campaign.model.event',
                ],
            ],
        ],
        'integrations' => [
            'mautic.integration.gotoassist' => [
                'class'     => \MauticPlugin\LeuchtfeuerGoToBundle\Integration\GotoassistIntegration::class,
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
                'class'     => \MauticPlugin\LeuchtfeuerGoToBundle\Integration\GotomeetingIntegration::class,
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
                'class'     => \MauticPlugin\LeuchtfeuerGoToBundle\Integration\GototrainingIntegration::class,
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
                'class'     => \MauticPlugin\LeuchtfeuerGoToBundle\Integration\GotowebinarIntegration::class,
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
