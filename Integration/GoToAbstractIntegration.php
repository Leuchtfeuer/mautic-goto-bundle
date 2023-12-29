<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Integration;

use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Integration\AbstractIntegration;

/**
 * Class GoToAbstractIntegration.
 */
abstract class GoToAbstractIntegration extends AbstractIntegration
{
    protected $auth;

    /**
     * @return array
     */
    public function getSupportedFeatures(): array
    {
        return [];
    }

    public function setIntegrationSettings(Integration $settings): void
    {
        // make sure URL does not have ending /
        $keys = $this->getDecryptedApiKeys($settings);
        if (array_key_exists('url', $keys) && '/' === substr($keys['url'], -1)) {
            $keys['url'] = substr($keys['url'], 0, -1);
            $this->encryptAndSetApiKeys($keys, $settings);
        }

        parent::setIntegrationSettings($settings);
    }

    /**
     * Refresh tokens.
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
     *
     * @return string
     */
    public function getAuthenticationType(): string
    {
        return 'oauth2';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
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

    /**
     * @return array
     */
    public function getFormSettings(): array
    {
        return [
            'requires_callback'      => true,
            'requires_authorization' => true,
        ];
    }

    /**
     * @return string
     */
    public function getApiUrl(): string
    {
        return 'https://api.getgo.com';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAccessTokenUrl(): string
    {
        return $this->getApiUrl().'/oauth/v2/token';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationUrl(): string
    {
        return $this->getApiUrl().'/oauth/v2/authorize';
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isAuthorized(): bool
    {
        $keys = $this->getKeys();

        return isset($keys[$this->getAuthTokenKey()]);
    }

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        $keys = $this->getKeys();

        return $keys[$this->getAuthTokenKey()];
    }

    /**
     * @return string
     */
    public function getOrganizerKey(): string
    {
        $keys = $this->getKeys();

        return $keys['organizer_key'];
    }

    public function getAccountKey()
    {
        $keys = $this->getKeys();

        return $keys['account_key'];
    }
}
