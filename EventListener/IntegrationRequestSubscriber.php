<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\EventListener;

use Mautic\PluginBundle\Event\PluginIntegrationRequestEvent;
use Mautic\PluginBundle\PluginEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class StatsSubscriber.
 */
class IntegrationRequestSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PluginEvents::PLUGIN_ON_INTEGRATION_REQUEST => [
                'getParameters',
                0,
            ],
        ];
    }

    /**
     * @throws \Exception
     */
    public function getParameters(PluginIntegrationRequestEvent $requestEvent): void
    {
        if (str_contains($requestEvent->getUrl(), 'oauth/token')) {
            $authorization = $this->getAuthorization($requestEvent->getParameters());
            $requestEvent->setHeaders([
                'Authorization' => sprintf('Basic %s', base64_encode($authorization)),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ]);
        }
    }

    /**
     * @param mixed[] $parameters
     *
     * @throws \Exception
     */
    protected function getAuthorization(array $parameters): string
    {
        if (empty($parameters['client_id'])) {
            throw new \Exception('No client ID given.', 1_554_211_764);
        }

        if (empty($parameters['client_secret'])) {
            throw new \Exception('No client secret given.', 1_554_211_808);
        }

        return sprintf('%s:%s', $parameters['client_id'], $parameters['client_secret']);
    }
}
