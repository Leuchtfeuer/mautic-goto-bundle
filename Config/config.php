<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'GoTo',
    'description' => 'Enables integration with Mautic supported GoTo collaboration products.',
    'version'     => '1.2',
    'author'      => 'Leuchtfeuer Digital Marketing',
    'routes'      => [
        'public' => [
            'mautic_citrix_proxy' => [
                'path'       => '/citrix/proxy',
                'controller' => 'MauticGoToBundle:Public:proxy',
            ],
            'mautic_citrix_sessionchanged' => [
                'path'       => '/citrix/sessionChanged',
                'controller' => 'MauticGoToBundle:Public:sessionChanged',
            ],
        ],
    ],
    'services' => [
        'events' => [
            'mautic.citrix.formbundle.subscriber' => [
                'class'     => \MauticPlugin\MauticGoToBundle\EventListener\FormSubscriber::class,
                'arguments' => [
                    'mautic.citrix.model.citrix',
                    'mautic.form.model.form',
                    'mautic.form.model.submission',
                    'translator',
                    'doctrine.orm.entity_manager',
                    'mautic.helper.templating',
                ],
                'methodCalls' => [
                    'setEmailModel' => ['mautic.email.model.email'],
                ],
            ],
            'mautic.citrix.leadbundle.subscriber' => [
                'class'     => \MauticPlugin\MauticGoToBundle\EventListener\LeadSubscriber::class,
                'arguments' => [
                    'mautic.citrix.model.citrix',
                    'doctrine.orm.entity_manager',
                    'translator',
                ],
            ],
            'mautic.citrix.campaignbundle.subscriber' => [
                'class'     => \MauticPlugin\MauticGoToBundle\EventListener\CampaignSubscriber::class,
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
                'class'     => \MauticPlugin\MauticGoToBundle\EventListener\EmailSubscriber::class,
                'arguments' => [
                    'mautic.citrix.model.citrix',
                    'translator',
                    'mautic.helper.templating',
                ],
            ],
            'mautic.citrix.stats.subscriber' => [
                'class'     => \MauticPlugin\MauticGoToBundle\EventListener\StatsSubscriber::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.citrix.integration.request' => [
                'class'     => \MauticPlugin\MauticGoToBundle\EventListener\IntegrationRequestSubscriber::class,
                'arguments' => [],
            ],
        ],
        'forms' => [
            'mautic.form.type.fieldslist.citrixlist' => [
                'class' => \MauticPlugin\MauticGoToBundle\Form\Type\GoToListType::class,
                'alias' => 'citrix_list',
                'arguments' => [
                    'mautic.citrix.model.citrix'
                ]
            ],
            'mautic.form.type.citrix.submitaction' => [
                'class'     => \MauticPlugin\MauticGoToBundle\Form\Type\GoToActionType::class,
                'alias'     => 'citrix_submit_action',
                'arguments' => [
                    'mautic.form.model.field',
                    'mautic.citrix.model.citrix'
                ],
            ],
            'mautic.form.type.citrix.campaignevent' => [
                'class'     => \MauticPlugin\MauticGoToBundle\Form\Type\GoToCampaignEventType::class,
                'alias'     => 'citrix_campaign_event',
                'arguments' => [
                    'mautic.citrix.model.citrix',
                    'translator',
                ],
            ],
            'mautic.form.type.citrix.campaignaction' => [
                'class'     => \MauticPlugin\MauticGoToBundle\Form\Type\GoToCampaignActionType::class,
                'alias'     => 'citrix_campaign_action',
                'arguments' => [
                    'mautic.citrix.model.citrix',
                    'translator',
                ],
            ],
        ],
        'models' => [
            'mautic.citrix.model.citrix' => [
                'class'     => \MauticPlugin\MauticGoToBundle\Model\GoToModel::class,
                'arguments' => [
                    'mautic.lead.model.lead',
                    'mautic.campaign.model.event',
                ],
            ],
        ],
        'integrations' => [
            'mautic.integration.gotoassist' => [
                'class'     => \MauticPlugin\MauticGoToBundle\Integration\GotoassistIntegration::class,
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
                'class'     => \MauticPlugin\MauticGoToBundle\Integration\GotomeetingIntegration::class,
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
                'class'     => \MauticPlugin\MauticGoToBundle\Integration\GototrainingIntegration::class,
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
                'class'     => \MauticPlugin\MauticGoToBundle\Integration\GotowebinarIntegration::class,
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
