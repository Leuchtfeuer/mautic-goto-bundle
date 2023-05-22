<?php

declare(strict_types=1);

namespace MauticPlugin\MauticGoToBundle\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Mautic\PluginBundle\Event\PluginUpdateEvent;
use Mautic\PluginBundle\PluginEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PluginEventSubscriber implements EventSubscriberInterface
{
    private Connection $connection;
    private LoggerInterface $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger     = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            PluginEvents::ON_PLUGIN_UPDATE => ['onPluginUpdate'],
        ];
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws Exception
     */
    public function onPluginUpdate(PluginUpdateEvent $event): void
    {
        if (!$event->checkContext('GoTo Integration by Leuchtfeuer')) {
            return;
        }

        if ('2.2.1' === $event->getOldVersion()) {
            $this->updateSegments();
        }
    }

    /**
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    private function updateSegments(): void
    {
        $results = $this->connection->fetchAllAssociative("SELECT * FROM lead_lists WHERE filters LIKE '%webinar-registration%' OR filters LIKE '%webinar-attendance%' OR filters LIKE '%webinar-no-attendance%'");

        if (empty($results)) {
            return;
        }

        $count = 0;
        foreach ($results as $segment) {
            $newFilters         = [];
            $newPropertyFilters = [];
            $filters            = unserialize($segment['filters']);
            foreach ($filters as $filter) {
                if (!isset($filter['properties'])) {
                    $newFilters[] = $filter;
                    continue;
                }

                if (
                    !in_array($filter['field'], ['webinar-attendance', 'webinar-no-attendance', 'webinar-registration'])
                    || is_array($filter['properties']['filter'])
                ) {
                    $newFilters[] = $filter;
                    continue;
                }

                $newPropertyFilters[$filter['field']][$filter['operator']]['glue'][]   = $filter['glue'];
                $newPropertyFilters[$filter['field']][$filter['operator']]['filter'][] = $filter['properties']['filter'];
            }

            if (empty($newPropertyFilters)) {
                $this->logger->alert(sprintf('No updated for %s (%s)', $segment['public_name'], $segment['id']));
                continue;
            }

            foreach ($newPropertyFilters as $field => $filters) {
                foreach ($filters as $key => $filter) {
                    $conversion = [
                        'field'    => $field,
                        'object'   => 'lead',
                        'type'     => 'select',
                        'operator' => 'including' === $key ? 'in' : '!in',
                        'glue'     => array_pop($filter['glue']),
                    ];

                    foreach ($filter['filter'] as $value) {
                        if ('Any Webinar' === $value) {
                            $conversion['properties']['filter'][] = 'any';
                            break;
                        }

                        preg_match('#^([^ ]+ +[^ ]+) +(.*)$#', $value, $matches);

                        $productSql = 'SELECT product_key FROM plugin_goto_products WHERE name = :name AND date LIKE :date;';
                        $productKey = $this->connection->fetchOne($productSql, [
                            'name' => $matches[2],
                            'date' => sprintf('%%%s%%', date('Y-m-d H:i', strtotime($matches[1]))),
                        ]);

                        if (false === $productKey) {
                            $productKey = $value;
                            $this->logger->critical(sprintf(
                                'Please updated the Segment %s (%s) manually as the "%s" GOTO product is unavailable for mapping.',
                                $segment['public_name'],
                                $segment['id'],
                                $value
                            ));
                        }

                        $conversion['properties']['filter'][] = $productKey;
                    }
                    $newFilters[] = $conversion;
                    unset($conversion);
                }
            }

            $this->connection->prepare('UPDATE lead_lists SET filters=:filters WHERE id = :id')->executeStatement([
                'filters' => serialize($newFilters),
                'id'      => $segment['id'],
            ]);

            $count++;

            $this->logger->alert(sprintf('Segment %s updated successfully', $segment['id']));
        }

        $this->logger->alert(sprintf('Total %s segments updated!!!', $count));
    }
}
