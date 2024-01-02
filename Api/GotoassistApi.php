<?php

namespace MauticPlugin\LeuchtfeuerGoToBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;
use MauticPlugin\LeuchtfeuerGoToBundle\Integration\GotoassistIntegration;

class GotoassistApi
{
    use GoToApi;
    public function __construct(
        private GotoassistIntegration $integration
    ) {
    }

    /**
     * @param string $operation
     * @param string $method
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function request($operation, array $parameters = [], $method = 'GET')
    {
        $settings = [
            'module'          => 'G2A',
            'method'          => $method,
            'parameters'      => $parameters,
            'requestSettings' => [
                'auth_type' => 'none',
                'query'     => [
                    'oauth_token' => $this->integration->getApiKey(),
                ],
            ],
        ];

        return $this->_request($operation, $settings, 'rest/v1');
    }
}
