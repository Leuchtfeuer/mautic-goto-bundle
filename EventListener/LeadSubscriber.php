<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticGoToBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event\LeadListFilteringEvent;
use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\Event\LeadListFiltersOperatorsEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\MauticGoToBundle\Entity\GoToEvent;
use MauticPlugin\MauticGoToBundle\Entity\GoToEventTypes;
use MauticPlugin\MauticGoToBundle\Entity\GoToProduct;
use MauticPlugin\MauticGoToBundle\Entity\GoToProductRepository;
use MauticPlugin\MauticGoToBundle\Helper\GoToHelper;
use MauticPlugin\MauticGoToBundle\Helper\GoToProductTypes;
use MauticPlugin\MauticGoToBundle\Model\GoToModel;
use MauticPlugin\MauticSocialBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class LeadSubscriber.
 */
class LeadSubscriber implements EventSubscriberInterface
{
    /**
     * @var GoToModel
     */
    protected $model;

    /**
     * LeadSubscriber constructor.
     *
     * @param GoToModel $model
     */
    public function __construct(GoToModel $model)
    {
        $this->model = $model;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE => ['onTimelineGenerate', 0],
            LeadEvents::LIST_FILTERS_CHOICES_ON_GENERATE => ['onListChoicesGenerate', 0],
            LeadEvents::LIST_FILTERS_OPERATORS_ON_GENERATE => ['onListOperatorsGenerate', 0],
            LeadEvents::LIST_FILTERS_ON_FILTERING => ['onListFiltering', 0],
        ];
    }

    /**
     * @param LeadListFiltersOperatorsEvent $event
     */
    public function onListOperatorsGenerate(LeadListFiltersOperatorsEvent $event)
    {
        // TODO: add custom operators
    }

    /**
     * @param LeadTimelineEvent $event
     *
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        /** @var GoToProductRepository $productRepository */
        $productRepository = $this->em->getRepository(GoToProduct::class);
        $activeProducts = [];
        foreach (GoToProductTypes::toArray() as $p) {
            if (GoToHelper::isAuthorized('Goto' . $p)) {
                $activeProducts[] = $p;
            }
        }
        if (0 === count($activeProducts)) {
            return;
        }

        foreach ($activeProducts as $product) {
            foreach ([GoToEventTypes::REGISTERED, GoToEventTypes::ATTENDED] as $type) {
                $eventType = $product . '.' . $type;
                if (!$event->isApplicable($eventType)) {
                    continue;
                }

                $eventTypeLabel = $this->translator->trans('plugin.citrix.timeline.event.' . $product . '.' . $type);
                $eventTypeName = $this->translator->trans('plugin.citrix.timeline.' . $product . '.' . $type);
                $event->addEventType($eventType, $eventTypeName);

                $citrixEvents = $this->model->getRepository()->getEventsForTimeline(
                    [$product, $type],
                    $event->getLeadId(),
                    $event->getQueryOptions()
                );

                // Add total number to counter
                $event->addToCounter($eventType, $citrixEvents);

                if (!$event->isEngagementCount()) {
                    if ($citrixEvents['total']) {
                        // Use a single entity class to help parse the name, description, etc without hydrating entities for every single event
                        $entity = new GoToEvent();

                        foreach ($citrixEvents['results'] as $citrixEvent) {
                            $entity->setGoToProduct($productRepository->find((int)$citrixEvent['citrix_product_id']));
                            $entity->setEventType($citrixEvent['event_type']);
                            $entity->setEventDate($citrixEvent['event_date']);

                            $event->addEvent(
                                [
                                    'event' => $eventType,
                                    'eventId' => $eventType . $citrixEvent['id'],
                                    'eventLabel' => $eventTypeName,
                                    'eventType' => $eventTypeLabel,
                                    'timestamp' => $entity->getEventDate(),
                                    'extra' => [
                                        'eventName' => $entity->getGoToProduct()->getName(),
                                        'eventId' => $entity->getId(),
                                        'eventDesc' => $entity->getGoToProduct()->getDescription(),
                                        'joinUrl' => $entity->getJoinUrl(),
                                    ],
                                    'contentTemplate' => 'MauticGoToBundle:SubscribedEvents\Timeline:citrix_event.html.php',
                                    'contactId' => $citrixEvent['lead_id'],
                                ]
                            );
                        }
                    }
                }
            }
        } // foreach $product
    }

    /**
     * @param LeadListFiltersChoicesEvent $event
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \InvalidArgumentException
     */
    public function onListChoicesGenerate(LeadListFiltersChoicesEvent $event)
    {
        $activeProducts = [];
        foreach (GoToProductTypes::toArray() as $p) {
            if (GoToHelper::isAuthorized('Goto' . $p)) {
                $activeProducts[] = $p;
            }
        }
        if (0 === count($activeProducts)) {
            return;
        }

        foreach ($activeProducts as $product) {
            $eventNames = $this->model->getDistinctEventNamesDesc($product);

            $eventNamesWithoutAny = array_merge(
                [
                    '-' => '-',
                ],
                $eventNames
            );

            $eventNamesWithAny = array_merge(
                [
                    '-' => '-',
                    'any' => $event->getTranslator()->trans('plugin.citrix.event.' . $product . '.any'),
                ],
                $eventNames
            );

            if (in_array($product, [GoToProductTypes::GOTOWEBINAR, GoToProductTypes::GOTOTRAINING])) {
                $event->addChoice(
                    'lead',
                    $product . '-registration',
                    [
                        'label' => $event->getTranslator()->trans('plugin.citrix.event.' . $product . '.registration'),
                        'properties' => [
                            'type' => 'select',
                            'list' => $eventNamesWithAny,
                        ],
                        'operators' => [
                            'in' => $event->getTranslator()->trans('mautic.core.operator.in'),
                            '!in' => $event->getTranslator()->trans('mautic.core.operator.notin'),
                        ],
                    ]
                );
            }

            $event->addChoice(
                'lead',
                $product . '-attendance',
                [
                    'label' => $event->getTranslator()->trans('plugin.citrix.event.' . $product . '.attendance'),
                    'properties' => [
                        'type' => 'select',
                        'list' => $eventNamesWithAny,
                    ],
                    'operators' => [
                        'in' => $event->getTranslator()->trans('mautic.core.operator.in'),
                        '!in' => $event->getTranslator()->trans('mautic.core.operator.notin'),
                    ],
                ]
            );

            $event->addChoice(
                'lead',
                $product . '-no-attendance',
                [
                    'label' => $event->getTranslator()->trans('plugin.citrix.event.' . $product . '.no.attendance'),
                    'properties' => [
                        'type' => 'select',
                        'list' => $eventNamesWithoutAny,
                    ],
                    'operators' => [
                        'in' => $event->getTranslator()->trans('mautic.core.operator.in'),
                    ],
                ]
            );
        } // foreach $product
    }

    /**
     * @param LeadListFilteringEvent $event
     */
    public function onListFiltering(LeadListFilteringEvent $event)
    {
        $activeProducts = [];
        foreach (GoToProductTypes::toArray() as $p) {
            if (GoToHelper::isAuthorized('Goto' . $p)) {
                $activeProducts[] = $p;
            }
        }
        if (0 === count($activeProducts)) {
            return;
        }

        $details = $event->getDetails();
        $leadId = $event->getLeadId();
        $em = $event->getEntityManager();
        $q = $event->getQueryBuilder();
        $alias = $event->getAlias();
        $func = $event->getFunc();
        $currentFilter = $details['field'];
        $citrixEventsTable = $em->getClassMetadata('MauticGoToBundle:GoToEvent')->getTableName();
        $citrixProductsTable = $em->getClassMetadata('MauticGoToBundle:GoToProduct')->getTableName();
        $leadTable = $em->getClassMetadata(Lead::class)->getTableName();

        foreach ($activeProducts as $product) {
            $eventFilters = [$product . '-registration', $product . '-attendance', $product . '-no-attendance'];

            if (in_array($currentFilter, $eventFilters, true)) {
                $eventNames = $details['filter'];
                $isAnyEvent = in_array('any', $eventNames, true);
                $eventNames = array_map(function ($v) use ($q) {
                    return $q->expr()->literal($v);
                }, $eventNames);
                $subQueriesSQL = [];

                $eventTypes = [GoToEventTypes::REGISTERED, GoToEventTypes::ATTENDED];
                foreach ($eventTypes as $k => $eventType) {
                    $query = $em->getConnection()->createQueryBuilder()
                        ->select('null')
                        ->from($citrixEventsTable, $alias . $k)
                        ->innerJoin($alias . $k, $citrixProductsTable, 'cpt'.$k,$alias.$k.'.citrix_product_id = cpt'.$k.'.id');




                    if (!$isAnyEvent) {
                        $query->where(
                            $q->expr()->andX(
                                $q->expr()->eq('cpt'.$k . '.product', $q->expr()->literal($product)),
                                $q->expr()->eq($alias . $k . '.event_type', $q->expr()->literal($eventType)),
                                $q->expr()->in($alias . $k . '.event_name', $eventNames),
                                $q->expr()->eq($alias . $k . '.contact_id', 'l.id')
                            )
                        );
                    } else {
                        $query->where(
                            $q->expr()->andX(
                                $q->expr()->eq('cpt'.$k . '.product', $q->expr()->literal($product)),
                                $q->expr()->eq($alias . $k . '.event_type', $q->expr()->literal($eventType)),
                                $q->expr()->eq($alias . $k . '.contact_id', 'l.id')
                            )
                        );
                    }

                    if ($leadId) {
                        $query->andWhere(
                            $query->expr()->eq($alias . $k . '.contact_id', $leadId)
                        );
                    }

                    $subQueriesSQL[$eventType] = $query->getSQL();
                } // foreach $eventType

                switch ($currentFilter) {
                    case $product . '-registration':
                        $event->setSubQuery(
                            sprintf('%s (%s)', 'in' == $func ? 'EXISTS' : 'NOT EXISTS',
                                $subQueriesSQL[GoToEventTypes::REGISTERED])
                        );
                        break;

                    case $product . '-attendance':
                        $event->setSubQuery(
                            sprintf('%s (%s)', 'in' == $func ? 'EXISTS' : 'NOT EXISTS',
                                $subQueriesSQL[GoToEventTypes::ATTENDED])
                        );
                        break;

                    case $product . '-no-attendance':
                        $queries = [
                            sprintf('%s (%s)', 'in' == $func ? 'NOT EXISTS' : 'EXISTS',
                                $subQueriesSQL[GoToEventTypes::ATTENDED])
                        ];

                        if (in_array($product, [GoToProductTypes::GOTOWEBINAR, GoToProductTypes::GOTOTRAINING])) {
                            // These products track registration
                            $queries[] = sprintf('EXISTS (%s)', $subQueriesSQL[GoToEventTypes::REGISTERED]);
                        }

                        $event->setSubQuery(implode(' AND ', $queries));

                        break;
                }
            }
        } // foreach $product
    }
}
