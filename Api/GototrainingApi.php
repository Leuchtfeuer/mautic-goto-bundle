<?php

namespace MauticPlugin\LeuchtfeuerGoToBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;
use MauticPlugin\LeuchtfeuerGoToBundle\Integration\GototrainingIntegration;

class GototrainingApi
{
    use GoToApi;

    public function __construct(
      private GototrainingIntegration $integration
    ) {
    }

    /**
     * @param mixed[] $parameters
     *
     * @throws ApiErrorException
     */
    public function request(string $operation, array $parameters = [], string $method = 'GET', string $route = 'rest'): mixed
    {
        $settings = [
            'module'     => 'G2T',
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

            return $this->_request($operation, $settings, $route);
        }

        return $this->_request($operation, $settings,
            sprintf('%s/organizers/%s', $route, $this->integration->getOrganizerKey()));
    }
}
