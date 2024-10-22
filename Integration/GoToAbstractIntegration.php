<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Integration;

use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Integration\AbstractIntegration;

/**
 * Class GoToAbstractIntegration.
 */
/** @phpstan-ignore-next-line */
abstract class GoToAbstractIntegration extends AbstractIntegration
{
    /**
     * @return mixed[]
     */
    public function getSupportedFeatures(): array
    {
        return [];
    }

    public function setIntegrationSettings(Integration $settings): void
    {
        // make sure URL does not have ending /
        /** @phpstan-ignore-next-line */
        $keys = $this->getDecryptedApiKeys($settings);
        if (array_key_exists('url', $keys) && str_ends_with($keys['url'], '/')) {
            $keys['url'] = substr($keys['url'], 0, -1);
        }
        /*
                // Fetch and set the account key
                $accountKey = $this->fetchAccountKey();
                if ($accountKey) {
                    $keys['account_key'] = $accountKey;
                }
        */

        $this->encryptAndSetApiKeys($keys, $settings);

        /** @phpstan-ignore-next-line  */
        parent::setIntegrationSettings($settings);
    }

    /**
     * Refresh tokens.
     *
     * @return string[]
     */
    public function getRefreshTokenKeys(): array
    {
        return [
            'refresh_token',
            'expires_in',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthenticationType(): string
    {
        return 'oauth2';
    }

    /**
     * @return array|string[]
     */
    public function getRequiredKeyFields(): array
    {
        return [
            'app_name'      => 'mautic.citrix.form.appname',
            'client_id'     => 'mautic.citrix.form.clientid',
            'client_secret' => 'mautic.citrix.form.clientsecret',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function sortFieldsAlphabetically(): bool
    {
        return false;
    }

    /**
     * Get the API helper.
     *
     * @return mixed
     */
    public function getApiHelper()
    {
        static $helper;
        if (null === $helper) {
            $class  = '\\MauticPlugin\\LeuchtfeuerGoToBundle\\Api\\'.$this->getName().'Api';
            $helper = new $class($this);
        }

        return $helper;
    }

    public function getFormSettings(): array
    {
        return [
            'requires_callback'      => true,
            'requires_authorization' => true,
        ];
    }

    public function getApiUrl(): string
    {
        return 'https://api.getgo.com';
    }

    public function getAuthBaseUrl(): string
    {
        return 'https://authentication.logmeininc.com';
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenUrl(): string
    {
        return $this->getAuthBaseUrl().'/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthenticationUrl(): string
    {
        return $this->getAuthBaseUrl().'/oauth/authorize';
    }

    public function getAccountUrl(): string
    {
        return 'https://api.getgo.com/admin/rest/v1/me';
    }

    public function getOrganizerUrl(): string
    {
        return $this->getAuthBaseUrl().'/organizers';
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthorized(): bool
    {
        $keys = $this->getKeys();

        return isset($keys[$this->getAuthTokenKey()]);
    }

    public function getApiKey(): string
    {
        $keys = $this->getKeys();

        return $keys[$this->getAuthTokenKey()];
    }

    public function getOrganizerKey(): string
    {
        $keys = $this->getKeys();

        return $keys['organizer_key'];
    }

    public function getAccountKey(): ?string
    {
        $keys = $this->getKeys();

        return $keys['account_key'] ?? null;
    }

    public function fetchAccountKey(): ?string
    {
        $url = '';
        $headers = '';
        $timeout = '';

        $options = [];
        $apiKey = $_GET['code'] ?? null;

        $client = $this->makeHttpClient($options);

        $result = $client->get($url, [
            RequestOptions::HEADERS => $headers,
            RequestOptions::TIMEOUT => $timeout,
        ]);

        /*
        $requestSettings = [
            'encode_parameters'   => 'json',
            'return_raw'          => 'true', // needed to get the HTTP status code in the response
            'override_auth_token' => 'oauth_token='.$this->getApiKey(),
        ];
        $response = $this->makeRequest($this->getAccountUrl(), [], 'GET',$requestSettings);
*/
        if (isset($response['accounts']) && !empty($response['accounts'])) {
            return $response['accounts'][0]['key'] ?? null;
        }

        return null;
    }

    public function fetchOrganizerKey(): ?string
    {
        return null;
    }

    public function parseCallbackResponse($data, $postAuthorization = false)
    {
        $accountKey = $this->fetchAccountKey();
        $organizerKey = $this->fetchOrganizerKey();
        // remove control characters that will break json_decode from parsing
        $data = preg_replace('/[[:cntrl:]]/', '', $data);
        if (!$parsed = json_decode($data, true)) {
            parse_str($data, $parsed);
        }

        return $parsed;
    }
}
