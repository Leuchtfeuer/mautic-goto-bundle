<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\EventListener;

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

        if ('3.0.0' === $event->getOldVersion()) {
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

        foreach ($results as $segment) {
            $filters = unserialize($segment['filters']);
            foreach ($filters as &$filter) {
                if (!isset($filter['properties'])) {
                    continue;
                }

                if (
                    !in_array($filter['field'], ['webinar-attendance', 'webinar-no-attendance', 'webinar-registration'])
                    || is_array($filter['properties']['filter'])
                ) {
                    continue;
                }

                $operator = $filter['operator'];
                if ('including' === $operator) {
                    $operator = 'in';
                } elseif ('excluding' == $operator) {
                    $operator = '!in';
                }

                $filter['operator'] = $operator;

                $selectedEvent = $filter['properties']['filter'];
                unset($filter['properties']['filter']);
                unset($filter['filter']);
                if ('Any Webinar' === $selectedEvent) {
                    $filter['properties']['filter'][] = 'any';
                    $filter['filter'][]               = 'any';
                    continue;
                }

                preg_match('#^([^ ]+ +[^ ]+) +(.*)$#', $selectedEvent, $matches);

                $productSql = 'SELECT product_key FROM plugin_goto_products WHERE name = :name AND date LIKE :date;';
                $productKey = $this->connection->fetchOne($productSql, [
                    'name' => $matches[2],
                    'date' => sprintf('%%%s%%', date('Y-m-d H:i', strtotime($matches[1]))),
                ]);

                if (false === $productKey) {
                    $productKey = null;
                    $message    = sprintf(
                        'Please update the segment "%s (%s)" manually as the "%s" GOTO product is unavailable for mapping under the "%s" operator.',
                        $segment['public_name'],
                        $segment['id'],
                        $selectedEvent,
                        '!in' === $operator ? 'Not in' : 'In'
                    );

                    $this->log($message, 'error');
                }

                $filter['properties']['filter'][] = $productKey;
                $filter['filter'][]               = $productKey;
            }

            $this->connection->prepare('UPDATE lead_lists SET filters=:filters WHERE id = :id')->executeStatement([
                'filters' => serialize($filters),
                'id'      => $segment['id'],
            ]);
        }

        $this->log(sprintf('Total %s segments migrated!', count($results)), 'info');
    }

    private function log(string $message, string $level): void
    {
        if ('cli' === php_sapi_name()) {
            dump($message);
        }

        $this->logger->$level($message);
    }
}
