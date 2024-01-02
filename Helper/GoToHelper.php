<?php

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
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class GoToHelper
{
    private IntegrationHelper $integrationHelper;
    private LoggerInterface $logger;
    private GotoassistApi $assistClient;
    private GotomeetingApi $meetingClient;
    private GotowebinarApi $webinarClient;
    private GototrainingApi $trainingClient;

    public function __construct(
        IntegrationHelper $integrationHelper,
        LoggerInterface $logger,
        GotoassistApi $assistClient,
        GotomeetingApi $meetingClient,
        GotowebinarApi $webinarClient,
        GototrainingApi $trainingClient
    ) {
        $this->integrationHelper = $integrationHelper;
        $this->logger            = $logger;
        $this->assistClient      = $assistClient;
        $this->meetingClient     = $meetingClient;
        $this->webinarClient     = $webinarClient;
        $this->trainingClient    = $trainingClient;
    }

    /**
     * Get the API helper.
     *
     * @return GotomeetingApi
     */
    public function getG2mApi()
    {
        static $g2mapi;
        if (null === $g2mapi) {
            $class  = '\\MauticPlugin\\LeuchtfeuerGoToBundle\\Api\\GotomeetingApi';
            $g2mapi = new $class($this->getIntegration('Gotomeeting'));
        }

        return $g2mapi;
    }

    /**
     * Get the API helper.
     *
     * @return GotowebinarApi
     */
    public function getG2wApi()
    {
        static $g2wapi;
        if (null === $g2wapi) {
            $class  = '\\MauticPlugin\\LeuchtfeuerGoToBundle\\Api\\GotowebinarApi';
            $g2wapi = new $class($this->getIntegration('Gotowebinar'));
        }

        return $g2wapi;
    }

    /**
     * Get the API helper.
     *
     * @return GototrainingApi
     */
    public function getG2tApi()
    {
        static $g2tapi;
        if (null === $g2tapi) {
            $class  = '\\MauticPlugin\\LeuchtfeuerGoToBundle\\Api\\GototrainingApi';
            $g2tapi = new $class($this->getIntegration('Gototraining'));
        }

        return $g2tapi;
    }

    /**
     * Get the API helper.
     *
     * @return GotoassistApi
     */
    public function getG2aApi()
    {
        static $g2aapi;
        if (null === $g2aapi) {
            $class  = '\\MauticPlugin\\LeuchtfeuerGoToBundle\\Api\\GotoassistApi';
            $g2aapi = new $class($this->getIntegration('Gotoassist'));
        }

        return $g2aapi;
    }

    /**
     * @param        $msg
     * @param string $level
     */
    public function log($msg, $level = 'error')
    {
        //  Make sure the logs are in the same timezone
        Logger::setTimezone(new \DateTimeZone(date_default_timezone_get()));

        try {
            $this->logger->log($level, $msg);
        } catch (\Exception $exception) {
            // do nothing
        }
    }

    /**
     * @param array $results
     * @param       $key
     * @param       $value
     *
     * @return mixed
     *
     * todo: bring back static / disabled it because of xdebug issues
     */
    public function getKeyPairsWithDetails($results, $key, $values = null)
    {
        $return_results = [];
        /** @var array $results */
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
     * @param array $results
     * @param       $key
     * @param       $value
     *
     * @return \Generator
     */
    public function getKeyPairs($results, $key, $value)
    {
        /** @var array $results */
        foreach ($results as $result) {
            if (array_key_exists($key, $result) && array_key_exists($value, $result)) {
                yield $result[$key] => $result[$value];
            }
        }
    }

    /**
     * @param array $sessions
     * @param bool  $showAll  Whether to show only active sessions
     *
     * @return \Generator
     */
    public function getAssistPairs($sessions, $showAll = false)
    {
        /** @var array $sessions */
        foreach ($sessions as $session) {
            if ($showAll || !in_array($session['status'], ['notStarted', 'abandoned'], true)) {
                yield $session['sessionId'] => sprintf('%s (%s)', $session['sessionId'], $session['status']);
            }
        }
    }

    /**
     * @param      $listType    string Can be one of 'webinar', 'meeting', 'training' or 'assist'
     * @param bool $onlyFutures
     *
     * @return array
     */
    public function getGoToChoices($listType, $onlyFutures = true, $withDetails = false)
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
        $myIntegration = $this->getIntegration($integration);;

        return $myIntegration && $myIntegration->getIntegrationSettings() && $myIntegration->getIntegrationSettings()->getIsPublished() && !empty($myIntegration->getKeys()[$myIntegration->getAuthTokenKey()]);
    }

    private function getIntegration(string $integration): ?AbstractIntegration
    {
        try {
            return $this->integrationHelper->getIntegrationObject($integration);
        } catch (\Exception $exception) {
            // do nothing
        }

        return null;
    }

    /**
     * @param $listType
     *
     * @return mixed
     */
    private function listToIntegration($listType)
    {
        if (GoToProductTypes::isValidValue($listType)) {
            return 'Goto'.$listType;
        }

        return '';
    }

    public function getWebinarDetails($webinarKey)
    {
        return $this->webinarClient->request('webinars/'.$webinarKey);
    }

    /**
     * @param string $str
     * @param int    $limit
     *
     * @return string
     */
    public function getCleanString($str, $limit = 20)
    {
        $str = htmlentities(strtolower($str), ENT_NOQUOTES, 'utf-8');
        $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str);
        $str = preg_replace('#&[^;]+;#', '', $str);

        $availableChars = explode(' ', '0 1 2 3 4 5 6 7 8 9 a b c d e f g h i j k l m n o p q r s t u v w x y z');
        $safeStr        = '';
        $safeChar       = '';
        /** @var array $chars */
        $chars = str_split($str);
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
     * @param $product
     * @param $productId
     * @param $email
     * @param $firstname
     * @param $lastname
     * @param $company
     *
     * @return bool
     *
     * @throws BadRequestHttpException
     */
    public function registerToProduct($product, $productId, $email, $firstname, $lastname, $company)
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
     * @param $product
     * @param $productId
     * @param $email
     * @param $firstname
     * @param $lastname
     *
     * @return bool
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function startToProduct($product, $productId, $email, $firstname, $lastname)
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
                    /** @var Router $router */
                    $router = $this->getContainer()->get('router');
                    $params = [
                        'sessionStatusCallbackUrl' => $router
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
            throw new BadRequestHttpException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return '';
    }

    /**
     * @param string $product
     * @param string $productId
     *
     * @return string
     *
     * @throws \Mautic\PluginBundle\Exception\ApiErrorException
     */
    public function getEventName($product, $productId)
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
     * @param string $product
     * @param string $productId
     *
     * @return array
     *
     * @throws \Mautic\PluginBundle\Exception\ApiErrorException
     */
    public function getRegistrants($product, $productId, $organizerKey)
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
     * @param string $product
     * @param string $productId
     *
     * @return array
     *
     * @throws \Mautic\PluginBundle\Exception\ApiErrorException
     */
    public function getAttendees($product, $productId, $organizerKey)
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
     * @param $results
     *
     * @return array
     */
    protected function extractContacts($results)
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

    public function getPanelists($product, $organizerKey, $productId)
    {
        try {
            return $this->webinarClient->request($product.'s/'.$productId.'/panelists', [], 'GET', $organizerKey);
        } catch (ApiErrorException $apiErrorException) {
            return false;
        }
    }
}
