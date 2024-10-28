<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Integration;

class GotoassistIntegration extends GoToAbstractIntegration
{
    public function getName(): string
    {
        return 'Gotoassist';
    }

    public function getDisplayName(): string
    {
        return 'GoToAssist';
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
        if (!array_key_exists('access_token', $parsed)) {
            return $parsed;
        }
        // when authentication request (authorize, reauthorize, token refresh, changing credentials)
        else {
            try {
                $parsed['account_key'] = $this->fetchAccountKey($parsed['access_token']);
            } catch (\Exception $e) {
                $this->logger->log('error', $e->getMessage());
            }
            try {
                $parsed['organizer_key'] = $this->fetchOrganizerKey($parsed['access_token']);
            } catch (\Exception) {
                $parsed['organizer_key'] = '';
            }

            return $parsed;
        }
    }
}
