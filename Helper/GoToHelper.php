<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Helper;

use Mautic\PluginBundle\Exception\ApiErrorException;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use MauticPlugin\LeuchtfeuerGoToBundle\Api\GotoassistApi;
use MauticPlugin\LeuchtfeuerGoToBundle\Api\GotomeetingApi;
use MauticPlugin\LeuchtfeuerGoToBundle\Api\GototrainingApi;
use MauticPlugin\LeuchtfeuerGoToBundle\Api\GotowebinarApi;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class GoToHelper
{
    public function __construct(
        private IntegrationHelper $integrationHelper,
        private LoggerInterface $logger,
        private GotoassistApi $assistClient,
        private GotomeetingApi $meetingClient,
        private GotowebinarApi $webinarClient,
        private GototrainingApi $trainingClient,
        private RouterInterface $router
    ) {
    }

    public function log(string $msg, string $level = 'error'): void
    {
        //  Make sure the logs are in the same timezone
        Logger::setTimezone(new \DateTimeZone(date_default_timezone_get()));

        try {
            $this->logger->log($level, $msg);
        } catch (\Exception) {
            // do nothing
        }
    }

    /**
     * @param mixed[] $results
     *
     * @return mixed[]
     *
     * todo: bring back static / disabled it because of xdebug issues
     */
    public function getKeyPairsWithDetails(array $results, mixed $key, mixed $values = null): array
    {
        $return_results = [];
        foreach ($results as $result) {
            $temp = [];
            if (null === $values) {
                if (array_key_exists($key, $result)) {
                    $return_results[$result[$key]] = $result;
                }
            } else {
                foreach ($values as $value) {
                    if (array_key_exists($key, $result) && array_key_exists($value, $result)) {
                        $temp[$value] = $result[$value];
                    }
                }

                $return_results[$result[$key]] = $temp[0];
            }
        }

        return $return_results;
    }

    /**
     * @param mixed[] $results
     */
    public function getKeyPairs(array $results, mixed $key, mixed $value): \Generator
    {
        foreach ($results as $result) {
            if (array_key_exists($key, $result) && array_key_exists($value, $result)) {
                yield $result[$key] => $result[$value];
            }
        }
    }

    /**
     * @param mixed[] $sessions
     */
    public function getAssistPairs(array $sessions, bool $showAll = false): \Generator
    {
        foreach ($sessions as $session) {
            if ($showAll || !in_array($session['status'], ['notStarted', 'abandoned'], true)) {
                yield $session['sessionId'] => sprintf('%s (%s)', $session['sessionId'], $session['status']);
            }
        }
    }

    /**
     * @param string $listType Can be one of 'webinar', 'meeting', 'training' or 'assist'
     *
     * @return mixed[]
     */
    public function getGoToChoices(string $listType, bool $onlyFutures = true, bool $withDetails = false): array
    {
        try {
            // Check if integration is enabled
            if (!$this->isAuthorized($this->listToIntegration($listType))) {
                throw new AuthenticationException('You are not authorized to view '.$listType);
            }

            $currentYear = date('Y');
            // TODO: the date range can be configured elsewhere
            $fromTime = ($currentYear - 10).'-01-01T00:00:00Z';
            $toTime   = ($currentYear + 10).'-01-01T00:00:00Z';
            switch ($listType) {
                case GoToProductTypes::GOTOWEBINAR:
                    $url    = 'webinars';
                    $params = [];
                    if ($onlyFutures) {
                        $fromTime = date('Y-m-d').'T'.date('H:i:s').'Z';
                    }

                    $params['fromTime'] = $fromTime;
                    $params['toTime']   = $toTime;
                    $params['size']     = 200;

                    $results = $this->webinarClient->requestAllWebinars($url, $params);

                    if ($withDetails) {
                        return $this->getKeyPairsWithDetails($results['_embedded']['webinars'], 'webinarKey');
                    }

                    return iterator_to_array($this->getKeyPairs($results['_embedded']['webinars'], 'webinarKey', 'subject'));

                case GoToProductTypes::GOTOMEETING:
                    $url    = 'upcomingMeetings';
                    $params = [];
                    if (!$onlyFutures) {
                        $url                 = 'historicalMeetings';
                        $params['startDate'] = $fromTime;
                        $params['endDate']   = $toTime;
                    }

                    $results = $this->meetingClient->request($url, $params);

                    return iterator_to_array($this->getKeyPairs($results, 'meetingId', 'subject'));

                case GoToProductTypes::GOTOTRAINING:
                    $results = $this->trainingClient->request('trainings');

                    return iterator_to_array($this->getKeyPairs($results, 'trainingKey', 'name'));

                case GoToProductTypes::GOTOASSIST:
                    // show sessions in the last month
                    // times must be in ISO format: YYYY-MM-ddTHH:mm:ssZ
                    $params = [
                        'fromTime' => preg_filter(
                            '/^(.+)[\+\-].+$/',
                            '$1Z',
                            date('c', strtotime('-1 month', time()))
                        ),
                        'toTime'      => preg_filter('/^(.+)[\+\-].+$/', '$1Z', date('c')),
                        'sessionType' => 'screen_sharing',
                    ];
                    $results = $this->assistClient->request('sessions', $params);
                    if ((array) $results && array_key_exists('sessions', $results)) {
                        return iterator_to_array($this->getAssistPairs($results['sessions']));
                    }
            }
        } catch (\Exception $exception) {
            $this->log($exception->getMessage());
        }

        return [];
    }

    public function isAuthorized(string $integration): bool
    {
        $myIntegration = $this->getIntegration($integration);

        return $myIntegration && $myIntegration->getIntegrationSettings()->getIsPublished() && !empty($myIntegration->getKeys()[$myIntegration->getAuthTokenKey()]);
    }

    /** @phpstan-ignore-next-line  */
    private function getIntegration(string $integration): ?AbstractIntegration
    {
        try {
            return $this->integrationHelper->getIntegrationObject($integration);
        } catch (\Exception) {
            // do nothing
        }

        return null;
    }

    private function listToIntegration(string $listType): string
    {
        if (GoToProductTypes::isValidValue($listType)) {
            return 'Goto'.$listType;
        }

        return '';
    }

    public function getWebinarDetails(string $webinarKey): mixed
    {
        return $this->webinarClient->request('webinars/'.$webinarKey);
    }

    public function getCleanString(string $str, int $limit = 20): string
    {
        $str = htmlentities(strtolower($str), ENT_NOQUOTES, 'utf-8');
        $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str);
        $str = preg_replace('#&[^;]+;#', '', $str);

        $availableChars = explode(' ', '0 1 2 3 4 5 6 7 8 9 a b c d e f g h i j k l m n o p q r s t u v w x y z');
        $safeStr        = '';
        $safeChar       = '';
        $chars          = str_split($str);
        foreach ($chars as $char) {
            if (!in_array($char, $availableChars, true)) {
                if ('-' !== $safeChar) {
                    $safeChar = '-';
                } else {
                    continue;
                }
            } else {
                $safeChar = $char;
            }

            $safeStr .= $safeChar;
        }

        return trim(substr($safeStr, 0, $limit), '-');
    }

    /**
     * @param mixed $productId
     * @param mixed $email
     * @param mixed $firstname
     * @param mixed $lastname
     * @param mixed $company
     *
     * @throws BadRequestHttpException
     */
    public function registerToProduct(string $product, $productId, $email, $firstname, $lastname, $company): bool
    {
        try {
            $response = [];
            if (GoToProductTypes::GOTOWEBINAR == $product) {
                $params = [
                    'email'        => $email,
                    'firstName'    => $firstname,
                    'lastName'     => $lastname,
                    'organization' => $company,
                ];
                $response = $this->webinarClient->request(
                    'webinars/'.$productId.'/registrants?resendConfirmation=true',
                    $params,
                    'POST'
                );
            } elseif (GoToProductTypes::GOTOTRAINING == $product) {
                $params = [
                    'email'     => $email,
                    'givenName' => $firstname,
                    'surname'   => $lastname,
                ];
                $response = $this->trainingClient->request(
                    'trainings/'.$productId.'/registrants',
                    $params,
                    'POST'
                );
            }

            return is_array($response) && array_key_exists('joinUrl', $response);
        } catch (\Exception $exception) {
            $this->log('registerToProduct: '.$exception->getMessage());
            throw new BadRequestHttpException($exception->getMessage(), $exception, $exception->getCode());
        }
    }

    /**
     * @param mixed $productId
     * @param mixed $email
     * @param mixed $firstname
     * @param mixed $lastname
     *
     * @throws BadRequestHttpException
     */
    public function startToProduct(string $product, $productId, $email, $firstname, $lastname): bool|string
    {
        try {
            switch ($product) {
                case GoToProductTypes::GOTOMEETING:
                    $response = $this->meetingClient->request(
                        'meetings/'.$productId.'/start'
                    );

                    return (is_array($response) && array_key_exists('hostURL', $response)) ? $response['hostURL'] : '';

                case GoToProductTypes::GOTOTRAINING:
                    $response = $this->trainingClient->request(
                        'trainings/'.$productId.'/start'
                    );

                    return (is_array($response) && array_key_exists('hostURL', $response)) ? $response['hostURL'] : '';

                case GoToProductTypes::GOTOASSIST:
                    // TODO: use the sessioncallback to update attendance status
                    $params = [
                        'sessionStatusCallbackUrl' => $this->router
                            ->generate(
                                'mautic_citrix_sessionchanged',
                                [],
                                UrlGeneratorInterface::ABSOLUTE_URL
                            ),
                        'sessionType'      => 'screen_sharing',
                        'partnerObject'    => '',
                        'partnerObjectUrl' => '',
                        'customerName'     => $firstname.' '.$lastname,
                        'customerEmail'    => $email,
                        'machineUuid'      => '',
                    ];

                    $response = $this->assistClient->request(
                        'sessions',
                        $params,
                        'POST'
                    );

                    return (is_array($response)
                        && array_key_exists(
                            'startScreenSharing',
                            $response
                        )
                        && array_key_exists(
                            'launchUrl',
                            $response['startScreenSharing']
                        )) ? $response['startScreenSharing']['launchUrl'] : '';
            }
        } catch (\Exception $exception) {
            $this->log('startProduct: '.$exception->getMessage());
            throw new BadRequestHttpException($exception->getMessage(), $exception, $exception->getCode());
        }

        return '';
    }

    /**
     * @throws ApiErrorException
     */
    public function getEventName(string $product, string $productId): string
    {
        switch ($product) {
            case GoToProductTypes::GOTOWEBINAR:
                $result = $this->webinarClient->request($product.'s/'.$productId);

                return $result['subject'];

            case GoToProductTypes::GOTOMEETING:
                $result = $this->meetingClient->request($product.'s/'.$productId);

                return $result[0]['subject'];

            case GoToProductTypes::GOTOTRAINING:
                $result = $this->trainingClient->request($product.'s/'.$productId);

                return $result['name'];
        }

        return $productId;
    }

    /**
     * @return mixed[]
     *
     * @throws ApiErrorException
     */
    public function getRegistrants(string $product, string $productId, string $organizerKey): array
    {
        $result = [];
        if (GoToProductTypes::GOTOWEBINAR == $product) {
            $result = $this->webinarClient->request($product.'s/'.$productId.'/registrants', [], 'GET', $organizerKey);
        } elseif (GoToProductTypes::GOTOTRAINING == $product) {
            $result = $this->trainingClient->request($product.'s/'.$productId.'/registrants', [], 'GET');
        }

        return $this->extractContacts($result);
    }

    /**
     * @return mixed[]
     *
     * @throws ApiErrorException
     */
    public function getAttendees(string $product, string $productId, string $organizerKey): array
    {
        $result = [];
        switch ($product) {
            case GoToProductTypes::GOTOWEBINAR:
                $result = $this->webinarClient->request($product.'s/'.$productId.'/attendees', [], 'GET', $organizerKey);
                break;

            case GoToProductTypes::GOTOMEETING:
                $result = $this->meetingClient->request($product.'s/'.$productId.'/attendees', [], 'GET');
                break;

            case GoToProductTypes::GOTOTRAINING:
                $reports  = $this->trainingClient->request($product.'s/'.$productId, [], 'GET', 'rest/reports');
                $sessions = array_column($reports, 'sessionKey');
                foreach ($sessions as $session) {
                    $result = $this->trainingClient->request(
                        'sessions/'.$session.'/attendees',
                        [],
                        'GET',
                        'rest/reports'
                    );
                    $arr    = array_column($result, 'email');
                    $result = array_merge($result, $arr);
                }

                break;
        }

        return $this->extractContacts($result);
    }

    /**
     * @param mixed[] $results
     *
     * @return mixed[]
     */
    protected function extractContacts(array $results): array
    {
        $contacts = [];

        foreach ($results as $result) {
            $emailKey = false;
            if (isset($result['attendeeEmail'])) {
                if (empty($result['attendeeEmail'])) {
                    // ignore
                    continue;
                }

                $emailKey = strtolower($result['attendeeEmail']);
                $names    = explode(' ', $result['attendeeName']);
                switch (count($names)) {
                    case 1:
                        $firstname = $names[0];
                        $lastname  = '';
                        break;
                    case 2:
                        [$firstname, $lastname] = $names;
                        break;
                    default:
                        $firstname = $names[0];
                        unset($names[0]);
                        $lastname = implode(' ', $names);
                }

                $contacts[$emailKey] = [
                    'firstname' => $firstname,
                    'lastname'  => $lastname,
                    'email'     => $result['attendeeEmail'],
                ];
            } elseif (!empty($result['email'])) {
                $emailKey            = strtolower($result['email']);
                $contacts[$emailKey] = [
                    'firstname' => $result['firstName'] ?? '',
                    'lastname'  => $result['lastName'] ?? '',
                    'email'     => $result['email'],
                    'joinUrl'   => $result['joinUrl'] ?? '',
                ];
            }

            if ($emailKey) {
                $eventDate = null;
                // Extract join/register time
                if (!empty($result['attendance']['joinTime'])) {
                    $eventDate = $result['attendance']['joinTime'];
                } elseif (!empty($result['joinTime'])) {
                    $eventDate = $result['joinTime'];
                } elseif (!empty($result['inSessionTimes']['joinTime'])) {
                    $eventDate = $result['inSessionTimes']['joinTime'];
                } elseif (!empty($result['registrationDate'])) {
                    $eventDate = $result['registrationDate'];
                }

                if ($eventDate) {
                    $contacts[$emailKey]['event_date'] = new \DateTime($eventDate);
                }
            }
        }

        return $contacts;
    }

    public function getPanelists(string $product, string $organizerKey, string $productId): mixed
    {
        try {
            return $this->webinarClient->request($product.'s/'.$productId.'/panelists', [], 'GET', $organizerKey);
        } catch (ApiErrorException) {
            return false;
        }
    }
}
