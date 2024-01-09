<?php

namespace MauticPlugin\LeuchtfeuerGoToBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;
use MauticPlugin\LeuchtfeuerGoToBundle\Integration\GotowebinarIntegration;

class GotowebinarApi
{
    use GoToApi;

    public function __construct(
        private GotowebinarIntegration $integration
    ) {
    }

    /**
     * @param mixed[] $parameters
     *
     * @throws ApiErrorException
     */
    public function request(string $operation, array $parameters = [], string $method = 'GET', string $organizerKey = null): mixed
    {
        $settings = [
            'module'          => 'G2W',
            'method'          => $method,
            'parameters'      => $parameters,
            'requestSettings' => [
                'headers' => [
                    'Accept' => 'application/json;charset=UTF-8',
                ],
            ],
        ];
        if (null === $organizerKey) {
            $organizerKey = $this->integration->getOrganizerKey();
        }

        return $this->_request($operation, $settings,
            sprintf('rest/v2/organizers/%s', $organizerKey));
    }

    /**
     * @param mixed[] $parameters
     *
     * @throws ApiErrorException
     */
    public function requestAllWebinars(string $operation, array $parameters = [], string $method = 'GET'): mixed
    {
        $settings = [
            'module'          => 'G2W',
            'method'          => $method,
            'parameters'      => $parameters,
            'requestSettings' => [
                'headers' => [
                    'Accept' => 'application/json;charset=UTF-8',
                ],
            ],
        ];

        return $this->_request($operation, $settings,
            sprintf('rest/v2/accounts/%s', $this->integration->getAccountKey()));
    }
}
