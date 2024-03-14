<?php

declare(strict_types=1);

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

    'parameters' => [
        'goto_api_blacklist_patterns' => [
            '/[<>]/',                    // Matches any occurrence of < or >
            '/\\\\\d+/',                 // Matches one or more digits after a backslash
            '/&#x?.*;/',                 // Matches strings starting with "&#x" and ending with ";"
            '/0x\d/',                    // Matches strings starting with "0x" followed by a digit
            '/d0x\d/',                   // Matches strings starting with "d0x" followed by a digit
            '/javascript:/i',            // Matches "javascript:" case-insensitively
            '/\|/',                      // Matches the pipe character
            '/`/',                       // Matches the backtick character
            '/&lt;|<|%3C|&#60;/',        // Matches various representations of the less-than sign
            '/\\\x3c|\\\x3C|\\\u003c|\\\u003C/',  // Matches different representations of < with backslashes and unicode
            '/\b(?:FSCommand|onAbort|onActivate|onAfterPrint|onAfterUpdate|onBeforeActivate|onBeforeCopy|onBeforeCut|onBeforeDeactivate|onBeforeEditFocus|onBeforePaste|onBeforePrint|onBeforeUnload|onBeforeUpdate|onBegin|onBlur|onBounce|onCellChange|onChange|onClick|onContextMenu|onControlSelect|onCopy|onCut|onDataAvailable|onDataSetChanged|onDataSetComplete|onDblClick|onDeactivate|onDrag|onDragEnd|onDragLeave|onDragEnter|onDragOver|onDragDrop|onDragStart|onDrop|onEnd|onError|onErrorUpdate|onFilterChange|onFinish|onFocus|onFocusIn|onFocusOut|onHashChange|onHelp|onInput|onKeyDown|onKeyPress|onKeyUp|onLayoutComplete|onLoad|onLoseCapture|onMediaComplete|onMediaError|onMessage|onMouseDown|onMouseEnter|onMouseLeave|onMouseMove|onMouseOut|onMouseOver|onMouseUp|onMouseWheel|onMove|onMoveEnd|onMoveStart|onOffline|onOnline|onOutOfSync|onPaste|onPause|onPopState|onProgress|onPropertyChange|onReadyStateChange|onRedo|onRepeat|onReset|onResize|onResizeEnd|onResizeStart|onResume|onReverse|onRowsEnter|onRowExit|onRowDelete|onRowInserted|onScroll|onSeek|onSelect|onSelectChange|onSelectStart|onStart|onStop|onStorage|onSyncRestored|onSubmit|onTimeError|onTrackChange|onUndo|onUnload|onURLFlip|seekSegmentTime)\b/',  // Matches specific event names
        ],
    ],
];
