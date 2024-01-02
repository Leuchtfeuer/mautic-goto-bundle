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
     * @param string $operation
     * @param string $method
     * @param null   $organizerKey
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function request($operation, array $parameters = [], $method = 'GET', $organizerKey = null)
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
     * @param string $operation
     * @param string $method
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function requestAllWebinars($operation, array $parameters = [], $method = 'GET')
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
