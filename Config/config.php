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
    'version'     => '1.1',
    'author'      => 'Mautic',
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
                'class'     => 'MauticPlugin\MauticGoToBundle\EventListener\FormSubscriber',
                'arguments' => [
                    'mautic.citrix.model.citrix',
                    'mautic.form.model.form',
                    'mautic.form.model.submission',
                ],
                'methodCalls' => [
                    'setEmailModel' => ['mautic.email.model.email'],
                ],
            ],
            'mautic.citrix.leadbundle.subscriber' => [
                'class'     => 'MauticPlugin\MauticGoToBundle\EventListener\LeadSubscriber',
                'arguments' => [
                    'mautic.citrix.model.citrix',
                ],
            ],
            'mautic.citrix.campaignbundle.subscriber' => [
                'class'     => 'MauticPlugin\MauticGoToBundle\EventListener\CampaignSubscriber',
                'arguments' => [
                    'mautic.citrix.model.citrix',
                ],
                'methodCalls' => [
                    'setEmailModel' => ['mautic.email.model.email'],
                ],
            ],
            'mautic.citrix.emailbundle.subscriber' => [
                'class'     => 'MauticPlugin\MauticGoToBundle\EventListener\EmailSubscriber',
                'arguments' => [
                    'mautic.citrix.model.citrix',
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
                'class' => 'MauticPlugin\MauticGoToBundle\Form\Type\GoToListType',
                'alias' => 'citrix_list',
                'arguments' => [
                    'mautic.citrix.model.citrix'
                ]
            ],
            'mautic.form.type.citrix.submitaction' => [
                'class'     => 'MauticPlugin\MauticGoToBundle\Form\Type\GoToActionType',
                'alias'     => 'citrix_submit_action',
                'arguments' => [
                    'mautic.form.model.field',
                ],
            ],
            'mautic.form.type.citrix.campaignevent' => [
                'class'     => 'MauticPlugin\MauticGoToBundle\Form\Type\GoToCampaignEventType',
                'alias'     => 'citrix_campaign_event',
                'arguments' => [
                    'mautic.citrix.model.citrix',
                    'translator',
                ],
            ],
            'mautic.form.type.citrix.campaignaction' => [
                'class'     => 'MauticPlugin\MauticGoToBundle\Form\Type\GoToCampaignActionType',
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
                ],
            ],
            'mautic.integration.gotomeeting' => [
                'class'     => \MauticPlugin\MauticGoToBundle\Integration\GotomeetingIntegration::class,
                'arguments' => [
                ],
            ],
            'mautic.integration.gototraining' => [
                'class'     => \MauticPlugin\MauticGoToBundle\Integration\GototrainingIntegration::class,
                'arguments' => [
                ],
            ],
            'mautic.integration.gotowebinar' => [
                'class'     => \MauticPlugin\MauticGoToBundle\Integration\GotowebinarIntegration::class,
                'arguments' => [
                ],
            ],
        ],
    ],
];
