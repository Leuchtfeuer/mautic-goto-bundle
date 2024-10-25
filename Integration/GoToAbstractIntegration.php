<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Integration;

use GuzzleHttp\RequestOptions;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use PHPUnit\Framework\InvalidArgumentException;

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
        return $this->getApiUrl().'/admin/rest/v1/me';
    }

    public function getOrganizerUrl(): string
    {
        return $this->getApiUrl().'/G2M/rest/organizers';
    }


    /**
     * {@inheritdoc}
     */
    public function isAuthorized(): bool
    {
        $keys = $this->getKeys();

        return isset($keys[$this->getAuthTokenKey()]);
    }

    public function getApiKey(): ?string
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

    public function fetchAccountKey(string $accessToken): string
    {
        $accountData = $this->fetchKey($accessToken, $this->getAccountUrl());

        if (!isset($accountData['accountKey'])) {
            throw new \Exception('Missing data in $accountData');
        }
        return (string) $accountData['accountKey'];
    }

    public function fetchOrganizerKey(string $accessToken): string
    {
        $organizerData = $this->fetchKey($accessToken, $this->getOrganizerUrl());

        if (!isset($organizerData[0]['organizerKey'])) {
            throw new \Exception('Missing data in $organizerData');
        }
        return (string) $organizerData[0]['organizerKey'];
    }

    public function parseCallbackResponse($data, $postAuthorization = false)
    {
        $data = (string) $data;
        // remove control characters that will break json_decode from parsing
        $data = preg_replace('/[[:cntrl:]]/', '', $data);
        if (!$parsed = json_decode($data, true)) {
            parse_str($data, $parsed);
        }

        // when regular non-authentication request (e.g. leuchtfeuer:goto:sync)
        if(!array_key_exists('access_token', $parsed)) {
            return $parsed;
        }
        // when authentication request (authorize, reauthorize, token refresh, changing credentials)
        else {
            $parsed['account_key'] = $this->fetchAccountKey($parsed['access_token']);
            try{
                $parsed['organizer_key'] = $this->fetchOrganizerKey($parsed['access_token']);
            }
            catch (\Exception $e) {
                $parsed['organizer_key'] = '';
            }
            return $parsed;
        }
    }


    /**
     *
     * @return array<string|int,mixed>
     */
    public function fetchKey(string $accessToken, string $url): array
    {
        $options = [
            CURLOPT_HEADER         => 1,
        ];


        $client = $this->makeHttpClient($options);

        $headers = [
            'Authorization' => 'Bearer '. $accessToken,
            'Accept' => 'application/json',

        ];
        $timeout = 10;


        $response = $client->get($url, [
            RequestOptions::HEADERS => $headers,
            RequestOptions::TIMEOUT => $timeout,
        ]);

        $body = $response->getBody();
        $result = $body->getContents();
        return  json_decode($result, true);
    }
}
