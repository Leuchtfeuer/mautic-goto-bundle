<?php

namespace MauticPlugin\MauticGoToBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;

class GotoassistApi extends GoToApi
{
    /**
     * @param string $operation
     * @param array  $parameters
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

        return parent::_request($operation, $settings, 'rest/v1');
    }
}
