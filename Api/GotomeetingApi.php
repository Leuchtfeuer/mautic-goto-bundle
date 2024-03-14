<?php

namespace MauticPlugin\LeuchtfeuerGoToBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;
use MauticPlugin\LeuchtfeuerGoToBundle\Integration\GotomeetingIntegration;

class GotomeetingApi
{
    use GoToApi;

    public function __construct(
        private GotomeetingIntegration $integration
    ) {
    }

    /**
     * @param mixed[] $parameters
     *
     * @throws ApiErrorException
     */
    public function request(string $operation, array $parameters = [], string $method = 'GET'): mixed
    {
        $settings = [
            'module'     => 'G2M',
            'method'     => $method,
            'parameters' => $parameters,
        ];

        if (preg_match('#start$#', $operation)) {
            $settings['requestSettings'] = [
                'auth_type' => 'none',
                'headers'   => [
                    'Authorization' => 'OAuth oauth_token='.$this->integration->getApiKey(),
                ],
            ];
        }

        return $this->_request($operation, $settings);
    }
}
