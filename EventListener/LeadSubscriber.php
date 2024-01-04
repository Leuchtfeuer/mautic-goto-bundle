<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Event\LeadListFilteringEvent;
use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\Event\LeadListFiltersOperatorsEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\LeuchtfeuerGoToBundle\Entity\GoToEvent;
use MauticPlugin\LeuchtfeuerGoToBundle\Entity\GoToEventTypes;
use MauticPlugin\LeuchtfeuerGoToBundle\Entity\GoToProduct;
use MauticPlugin\LeuchtfeuerGoToBundle\Entity\GoToProductRepository;
use MauticPlugin\LeuchtfeuerGoToBundle\Helper\GoToHelper;
use MauticPlugin\LeuchtfeuerGoToBundle\Helper\GoToProductTypes;
use MauticPlugin\LeuchtfeuerGoToBundle\Model\GoToModel;
use MauticPlugin\MauticSocialBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class LeadSubscriber.
 */
class LeadSubscriber implements EventSubscriberInterface
{
    /**
     * LeadSubscriber constructor.
     */
    public function __construct(
        private GoToModel           $model,
        private EntityManager       $entityManager,
        private TranslatorInterface $translator,
        private GoToHelper          $goToHelper
    ) {
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE               => ['onTimelineGenerate', 0],
            LeadEvents::LIST_FILTERS_CHOICES_ON_GENERATE   => ['onListChoicesGenerate', 0],
            LeadEvents::LIST_FILTERS_OPERATORS_ON_GENERATE => ['onListOperatorsGenerate', 0],
            LeadEvents::LIST_FILTERS_ON_FILTERING          => ['onListFiltering', 0],
        ];
    }

    public function onListOperatorsGenerate(LeadListFiltersOperatorsEvent $event)
    {
        // TODO: add custom operators
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        /** @var GoToProductRepository $productRepository */
        $productRepository = $this->entityManager->getRepository(GoToProduct::class);
        $activeProducts    = [];
        foreach (GoToProductTypes::toArray() as $p) {
            if ($this->goToHelper->isAuthorized('Goto'.$p)) {
                $activeProducts[] = $p;
            }
        }

        if ([] === $activeProducts) {
            return;
        }

        foreach ($activeProducts as $product) {
            foreach ([GoToEventTypes::REGISTERED, GoToEventTypes::ATTENDED] as $type) {
                $eventType = $product.'.'.$type;
                if (!$event->isApplicable($eventType)) {
                    continue;
                }

                $eventTypeLabel = $this->translator->trans('plugin.citrix.timeline.event.'.$product.'.'.$type);
                $eventTypeName  = $this->translator->trans('plugin.citrix.timeline.'.$product.'.'.$type);
                $event->addEventType($eventType, $eventTypeName);

                $citrixEvents = $this->model->getRepository()->getEventsForTimeline(
                    [$product, $type],
                    $event->getLeadId(),
                    $event->getQueryOptions()
                );

                // Add total number to counter
                $event->addToCounter($eventType, $citrixEvents);

                if (!$event->isEngagementCount() && $citrixEvents['total']) {
                    // Use a single entity class to help parse the name, description, etc without hydrating entities for every single event
                    $entity = new GoToEvent();
                    foreach ($citrixEvents['results'] as $citrixEvent) {
                        $entity->setGoToProduct($productRepository->find((int) $citrixEvent['citrix_product_id']));
                        $entity->setEventType($citrixEvent['event_type']);
                        $entity->setEventDate($citrixEvent['event_date']);

                        $event->addEvent(
                            [
                                'event'      => $eventType,
                                'eventId'    => $eventType.$citrixEvent['id'],
                                'eventLabel' => $eventTypeName,
                                'eventType'  => $eventTypeLabel,
                                'timestamp'  => $entity->getEventDate(),
                                'extra'      => [
                                    'eventName' => $entity->getGoToProduct()->getName(),
                                    'eventId'   => $entity->getId(),
                                    'eventDesc' => $entity->getGoToProduct()->getDescription(),
                                    'joinUrl'   => $entity->getJoinUrl(),
                                ],
//                                'contentTemplate' => 'LeuchtfeuerGoToBundle:SubscribedEvents\Timeline:citrix_event.html.php',
//                                'contentTemplate' => '@LeuchtfeuerGoTo\SubscribedEvents\Timeline\citrix_event.html.php',
                                'contentTemplate' => '@LeuchtfeuerGoTo\SubscribedEvents\Timeline\citrix_event.html.twig',
                                'contactId'       => $event->getLeadId(),
                            ]
                        );
                    }
                }
            }
        }
        // foreach $product
    }

    /**
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \InvalidArgumentException
     */
    public function onListChoicesGenerate(LeadListFiltersChoicesEvent $event)
    {
        $activeProducts = [];
        foreach (GoToProductTypes::toArray() as $p) {
            if ($this->goToHelper->isAuthorized('Goto'.$p)) {
                $activeProducts[] = $p;
            }
        }

        if ([] === $activeProducts) {
            return;
        }

        foreach ($activeProducts as $product) {
            $eventNames           = $this->model->getDistinctEventNamesDesc($product);
            $eventNames           = array_combine($eventNames, $eventNames);
            $eventNamesWithoutAny = array_merge(
                [
                    '-' => '-',
                ],
                $eventNames
            );

            $eventNamesWithAny = array_merge(
                [
                    '-'   => '-',
                    'any' => $event->getTranslator()->trans('plugin.citrix.event.'.$product.'.any'),
                ],
                $eventNames
            );

            if (in_array($product, [GoToProductTypes::GOTOWEBINAR, GoToProductTypes::GOTOTRAINING])) {
                $event->addChoice(
                    'lead',
                    $product.'-registration',
                    [
                        'label'      => $event->getTranslator()->trans('plugin.citrix.event.'.$product.'.registration'),
                        'properties' => [
                            'type' => 'select',
                            'list' => $eventNamesWithAny,
                        ],
                        'operators' => [
                            'in'  => $event->getTranslator()->trans('mautic.core.operator.in'),
                            '!in' => $event->getTranslator()->trans('mautic.core.operator.notin'),
                        ],
                    ]
                );
            }

            $event->addChoice(
                'lead',
                $product.'-attendance',
                [
                    'label'      => $event->getTranslator()->trans('plugin.citrix.event.'.$product.'.attendance'),
                    'properties' => [
                        'type' => 'select',
                        'list' => $eventNamesWithAny,
                    ],
                    'operators' => [
                        'in'  => $event->getTranslator()->trans('mautic.core.operator.in'),
                        '!in' => $event->getTranslator()->trans('mautic.core.operator.notin'),
                    ],
                ]
            );

            $event->addChoice(
                'lead',
                $product.'-no-attendance',
                [
                    'label'      => $event->getTranslator()->trans('plugin.citrix.event.'.$product.'.no.attendance'),
                    'properties' => [
                        'type' => 'select',
                        'list' => $eventNamesWithoutAny,
                    ],
                    'operators' => [
                        'in' => $event->getTranslator()->trans('mautic.core.operator.in'),
                    ],
                ]
            );
        }
        // foreach $product
    }

    public function onListFiltering(LeadListFilteringEvent $event)
    {
        $activeProducts = [];
        foreach (GoToProductTypes::toArray() as $p) {
            if ($this->goToHelper->isAuthorized('Goto'.$p)) {
                $activeProducts[] = $p;
            }
        }

        if ([] === $activeProducts) {
            return;
        }

        $details             = $event->getDetails();
        $leadId              = $event->getLeadId();
        $em                  = $event->getEntityManager();
        $q                   = $event->getQueryBuilder();
        $alias               = $event->getAlias();
        $func                = $event->getFunc();
        $currentFilter       = $details['field'];
        $citrixEventsTable   = $em->getClassMetadata('LeuchtfeuerGoToBundle:GoToEvent')->getTableName();
        $citrixProductsTable = $em->getClassMetadata('LeuchtfeuerGoToBundle:GoToProduct')->getTableName();
        $leadTable           = $em->getClassMetadata(Lead::class)->getTableName();

        foreach ($activeProducts as $product) {
            $eventFilters = [$product.'-registration', $product.'-attendance', $product.'-no-attendance'];

            if (in_array($currentFilter, $eventFilters, true)) {
                $eventNames = $details['filter'];
                preg_match('#^([^ ]+ +[^ ]+) +(.*)$#', $eventNames, $matches);
                $eventNames = $this->model->getIdByNameAndDate($matches[2], $matches[1]);
                if (!$eventNames) {
                    break;
                }
                $isAnyEvent    = in_array('any', $eventNames, true);
                $subQueriesSQL = [];

                $eventTypes = [GoToEventTypes::REGISTERED, GoToEventTypes::ATTENDED];
                foreach ($eventTypes as $k => $eventType) {
                    $query = $em->getConnection()->createQueryBuilder()
                        ->select('null')
                        ->from($citrixEventsTable, $alias.$k)
                        ->innerJoin($alias.$k, $citrixProductsTable, 'cpt'.$k, $alias.$k.'.citrix_product_id = cpt'.$k.'.id');

                    if (!$isAnyEvent) {
                        $query->where(
                            $q->expr()->andX(
                                $q->expr()->eq($alias.$k.'.event_type', $q->expr()->literal($eventType)),
                                $q->expr()->eq($alias.$k.'.citrix_product_id', $eventNames),
                                $q->expr()->eq($alias.$k.'.contact_id', 'l.id')
                            )
                        );
                    } else {
                        $query->where(
                            $q->expr()->andX(
                                $q->expr()->eq($alias.$k.'.event_type', $q->expr()->literal($eventType)),
                                $q->expr()->eq($alias.$k.'.contact_id', 'l.id')
                            )
                        );
                    }

                    if ($leadId) {
                        $query->andWhere(
                            $query->expr()->eq($alias.$k.'.contact_id', $leadId)
                        );
                    }

                    $subQueriesSQL[$eventType] = $query->getSQL();
                }
                // foreach $eventType

                switch ($currentFilter) {
                    case $product.'-registration':
                        $event->setSubQuery(
                            sprintf('%s (%s)', 'including' === $func ? 'EXISTS' : 'NOT EXISTS',
                                $subQueriesSQL[GoToEventTypes::REGISTERED])
                        );
                        break;

                    case $product.'-attendance':
                        $event->setSubQuery(
                            sprintf('%s (%s)', 'including' === $func ? 'EXISTS' : 'NOT EXISTS',
                                $subQueriesSQL[GoToEventTypes::ATTENDED])
                        );
                        break;

                    case $product.'-no-attendance':
                        $queries = [
                            sprintf('%s (%s)', 'including' === $func ? 'NOT EXISTS' : 'EXISTS',
                                $subQueriesSQL[GoToEventTypes::ATTENDED]),
                        ];

                        if (in_array($product, [GoToProductTypes::GOTOWEBINAR, GoToProductTypes::GOTOTRAINING])) {
                            // These products track registration
                            $queries[] = sprintf('EXISTS (%s)', $subQueriesSQL[GoToEventTypes::REGISTERED]);
                        }

                        $event->setSubQuery(implode(' AND ', $queries));

                        break;
                }
            }
        }
        // foreach $product
    }
}
