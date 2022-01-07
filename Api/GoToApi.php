<?php

namespace MauticPlugin\MauticGoToBundle\Api;

use Joomla\Http\Response;
use Mautic\PluginBundle\Exception\ApiErrorException;
use MauticPlugin\MauticGoToBundle\Integration\GoToAbstractIntegration;
use Psr\Http\Message\ResponseInterface;

class GoToApi
{
    /**
     * @var GoToAbstractIntegration
     */
    protected $integration;

    /**
     * GoToApi constructor.
     *
     * @param GoToAbstractIntegration $integration
     */
    public function __construct(GoToAbstractIntegration $integration)
    {
        $this->integration = $integration;
    }

    /**
     * @param string $operation
     * @param array  $settings
     * @param string $route
     * @param bool   $refreshToken
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    protected function _request($operation, array $settings, $route = 'rest', $refreshToken = true)
    {
        $requestSettings = [
            'encode_parameters'   => 'json',
            'return_raw'          => 'true', // needed to get the HTTP status code in the response
            'override_auth_token' => 'oauth_token='.$this->integration->getApiKey(),
        ];

        if (array_key_exists('requestSettings', $settings) && is_array($settings['requestSettings'])) {
            $requestSettings = array_merge($requestSettings, $settings['requestSettings']);
        }

        $url = sprintf(
            '%s/%s/%s/%s',
            $this->integration->getApiUrl(),
            $settings['module'],
            $route,
            $operation
        );
        /** @var Response $request */
        $request = $this->integration->makeRequest(
            $url,
            $settings['parameters'],
            $settings['method'],
            $requestSettings
        );
        if(is_array($request) && isset($request['error'])) {
            $e = new ApiErrorException("GoToApi Error:" . $request['error']['message']);
            $this->integration->logIntegrationError($e);
            throw $e;
        }

        $status  = $request->code;
        $message = '';

        // Try refresh access_token with refresh_token (https://goto-developer.logmeininc.com/how-use-refresh-tokens)
        if ($refreshToken && $this->isInvalidTokenFromResponse($request)) {
            $error = $this->integration->authCallback(['use_refresh_token' => true]);
            if (!$error) {
                // keys changes, load new integration object
                return $this->_request($operation, $settings, $route, false);
            }
        }

        switch ($status) {
            case 200:
                // request ok
                break;
            case 201:
                // POST ok
                break;
            case 204:
                // PUT/DELETE ok
                break;
            case 400:
                $message = 'Bad request';
                break;
            case 401:
                $message = 'Unauthorized';
                break;
            case 403:
                $message = 'Token invalid';
                break;
            case 404:
                $message = 'The requested object does not exist';
                break;
            case 409:
                $message = 'The user is already registered';
                break;
            default:
                $message = $request->body;
                break;
        }

        if ($message !== '') {
            throw new ApiErrorException($message);
        }

        return $this->integration->parseCallbackResponse($request->body);
    }

    private function isInvalidTokenFromResponse(ResponseInterface $request): bool
    {
        $responseData = $this->integration->parseCallbackResponse($request->body);
        if (isset($responseData['int_err_code']) && $responseData['int_err_code'] == 'InvalidToken') {
            return true;
        }

        return false;
    }
}
