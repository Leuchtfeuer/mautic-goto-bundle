<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\LeuchtfeuerGoToBundle\Entity\GoToEvent;
use MauticPlugin\LeuchtfeuerGoToBundle\Entity\GoToEventTypes;
use MauticPlugin\LeuchtfeuerGoToBundle\Entity\GoToProduct;
use MauticPlugin\LeuchtfeuerGoToBundle\Entity\GoToProductRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use const MauticPlugin\LeuchtfeuerGoToBundle\Entity\STATUS_ACTIVE;
use MauticPlugin\LeuchtfeuerGoToBundle\Event\GoToEventUpdateEvent;
use MauticPlugin\LeuchtfeuerGoToBundle\GoToEvents;
use MauticPlugin\LeuchtfeuerGoToBundle\Helper\GoToHelper;
use MauticPlugin\LeuchtfeuerGoToBundle\Helper\GoToProductTypes;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GoToModel.
 */
class GoToModel extends FormModel
{
    protected LeadModel $leadModel;
    protected EventModel $eventModel;

    /**
     * GoToModel constructor.
     */
    public function __construct(
        LeadModel $leadModel,
        EventModel $eventModel,
        protected GoToHelper $goToHelper,
        protected EntityManagerInterface $em,
        protected CorePermissions $security,
        protected EventDispatcherInterface $dispatcher,
        protected UrlGeneratorInterface $router,
        protected Translator $translator,
        protected UserHelper $userHelper,
        protected LoggerInterface $logger,
        protected CoreParametersHelper $coreParametersHelper
    ) {
        $this->leadModel  = $leadModel;
        $this->eventModel = $eventModel;

        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $logger, $coreParametersHelper);
    }

    /**
     * {@inheritdoc}
     *
     * @return \MauticPlugin\LeuchtfeuerGoToBundle\Entity\GoToEventRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(GoToEvent::class);
    }

    /**
     * @param string    $product
     * @param string    $email
     * @param string    $eventName
     * @param string    $eventDesc
     * @param Lead      $lead
     * @param string    $eventType
     * @param \DateTime $eventDate
     *
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function addEvent($product, $email, $eventName, $eventDesc, $eventType, $lead, \DateTime $eventDate = null)
    {
        if (!GoToProductTypes::isValidValue($product) || !GoToEventTypes::isValidValue($eventType)) {
            $this->goToHelper->log('addEvent: incorrect data');

            return;
        }

        $productRepository = $this->em->getRepository(GoToProduct::class);
        $productKey        = explode('#', $eventName);
        $goToProduct       = $productRepository->findOneByProductKey($productKey[1]);
        if (null === $goToProduct) {
            $goToProduct = new GoToProduct();
        }

        $goToEvent = new GoToEvent();
        $goToProduct->setName($eventName);
        $goToProduct->setDescription($eventDesc);

        $goToEvent->setGoToProduct($goToProduct);
        $goToEvent->setContact($lead);
        $goToEvent->setEventType($eventType);

        if (null !== $eventDate) {
            $goToEvent->setEventDate($eventDate);
        }

        $this->em->persist($goToEvent);
        $this->em->flush();
    }

    /**
     * @param string $product
     * @param string $email
     *
     * @return array
     */
    public function getEventsByLeadEmail($product, $email)
    {
        if (!GoToProductTypes::isValidValue($product)) {
            return []; // is not a valid citrix product
        }

        return $this->getRepository()->findByEmail($product, $email);
    }

    /**
     * @param string $product
     * @param string $productId
     * @param string $eventType
     *
     * @return array
     */
    public function getEmailsByEvent($product, $productId, $eventType)
    {
        /** @var GoToProductRepository $productRepository */
        $productRepository = $this->em->getRepository(GoToProduct::class);

        if (!GoToProductTypes::isValidValue($product) || !GoToEventTypes::isValidValue($eventType)) {
            return []; // is not a valid goto product
        }

        $goToEvents = $this->getRepository()->findBy(
            [
                'citrixProduct' => $productRepository->findOneByProductKey($productId),
                'eventType'     => $eventType,
            ]
        );

        $emails = [];
        if (0 !== (is_countable($goToEvents) ? count($goToEvents) : 0)) {
            $emails = array_map(
                static fn (GoToEvent $citrixEvent) => $citrixEvent->getContact()->getEmail(),
                $goToEvents
            );
        }

        return $emails;
    }

    /**
     * @param string $product
     *
     * @return array
     */
    public function getDistinctEventNames($product)
    {
        if (!GoToProductTypes::isValidValue($product)) {
            return []; // is not a valid citrix product
        }

        $dql = sprintf(
            "SELECT DISTINCT(c.eventName) FROM LeuchtfeuerGoToBundle:GoToEvent c WHERE c.product='%s'",
            $product
        );
        $query = $this->em->createQuery($dql);
        $items = $query->getResult();

        return array_map(
            static fn ($item) => array_pop($item),
            $items
        );
    }

    /**
     * @param string $product
     *
     * @return array
     */
    public function getDistinctEventNamesDesc($product)
    {
        if (!GoToProductTypes::isValidValue($product)) {
            return []; // is not a valid citrix product
        }

        $dql = sprintf(
            "SELECT DISTINCT c.product_key, c.name, c.date FROM LeuchtfeuerGoToBundle:GoToProduct c WHERE c.product='%s'",
            $product
        );
        $query  = $this->em->createQuery($dql);
        $items  = $query->getResult();
        $result = [];
        foreach ($items as $item) {
            $eventDate = $item['date'];

            $result[$item['product_key']] = $eventDate->format('d.m.Y H:i').' '.$item['name'];
        }

        return $result;
    }

    /**
     * @param string $product
     * @param string $email
     * @param string $eventType
     *
     * @return int
     */
    public function countEventsBy($product, $email, $eventType, array $eventNames = [])
    {
        if (!GoToProductTypes::isValidValue($product) || !GoToEventTypes::isValidValue($eventType)) {
            return 0; // is not a valid citrix product
        }

        /*
         * SELECT * FROM mautic_developing.plugin_goto_events as pge
         * INNER JOIN mautic_developing.plugin_goto_products as pgp ON pge.citrix_product_id=pgp.id;
         */
        $dql = 'SELECT COUNT(c.id) AS cant FROM LeuchtfeuerGoToBundle:GoToEvent c '.
            ' INNER JOIN LeuchtfeuerGoToBundle:GoToProduct as p'.
            ' INNER JOIN MauticLeadBundle:Lead as l'.
            ' WHERE p.product=:product AND l.email=:email AND c.eventType=:eventType ';

        if ([] !== $eventNames) {
            $dql .= 'AND (';
            foreach (array_keys($eventNames) as $key) {
                $dql .= 'c.joinUrl Like :event'.$key;
                if (count($eventNames) > ($key + 1)) {
                    $dql .= ' OR ';
                } else {
                    $dql .= ')';
                }
            }
        }

        $query = $this->em->createQuery($dql);
        $query->setParameters([
            ':product'   => $product,
            ':email'     => $email,
            ':eventType' => $eventType,
        ]);
        if ([] !== $eventNames) {
            foreach ($eventNames as $key=>$event) {
                $query->setParameter(':event'.$key, '%'.$event.'%');
            }
        }

        $test = $query->getSQL();

        return (int) $query->getResult()[0]['cant'];
    }

    /**
     * @param      $product
     * @param      $productId
     * @param      $eventName
     * @param      $eventDesc
     * @param int  $count
     * @param null $output
     */
    public function syncEvent($product, $productId, $eventName, $eventDesc, &$count = 0, $output = null)
    {
        $cpr = $this->em->getRepository(GoToProduct::class);
        /** @var GoToProduct $product_result */
        $product_result   = $cpr->findOneBy(['product_key' => $productId]);
        $organizerKey     = $product_result->getOrganizerKey();
        $registrants      = $this->goToHelper->getRegistrants($product, $productId, $organizerKey);
        $knownRegistrants = $this->getEmailsByEvent(
            $product,
            $productId,
            GoToEventTypes::REGISTERED
        );

        [$registrantsToAdd, $registrantsToDelete] = $this->filterEventContacts($registrants, $knownRegistrants);
        $count += $this->batchAddAndRemove(
            $product,
            $eventName,
            $productId,
            GoToEventTypes::REGISTERED,
            $registrantsToAdd,
            $registrantsToDelete,
            $output
        );
        unset($registrants, $knownRegistrants, $registrantsToAdd, $registrantsToDelete);

        $attendees      = $this->goToHelper->getAttendees($product, $productId, $organizerKey);
        $knownAttendees = $this->getEmailsByEvent(
            $product,
            $eventName,
            GoToEventTypes::ATTENDED
        );

        [$attendeesToAdd, $attendeesToDelete] = $this->filterEventContacts($attendees, $knownAttendees);
        $count += $this->batchAddAndRemove(
            $product,
            $eventName,
            $productId,
            GoToEventTypes::ATTENDED,
            $attendeesToAdd,
            $attendeesToDelete,
            $output
        );
        unset($attendees, $knownAttendees, $attendeesToAdd, $attendeesToDelete);
    }

    /**
     * @param string          $product
     * @param string          $eventName
     * @param string          $productKey
     * @param string          $eventType
     * @param OutputInterface $output
     *
     * @return int
     *
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function batchAddAndRemove(
        $product,
        $eventName,
        $productKey,
        $eventType,
        array $contactsToAdd = [],
        array $emailsToRemove = [],
        OutputInterface $output = null
    ) {
        if (!GoToProductTypes::isValidValue($product) || !GoToEventTypes::isValidValue($eventType)) {
            return 0;
        }

        $count       = 0;
        $newEntities = [];

        // Add events
        if ([] !== $contactsToAdd) {
            $searchEmails = array_keys($contactsToAdd);
            $leads        = $this->leadModel->getRepository()->getLeadsByFieldValue('email', $searchEmails, null, true);
            // todo give as arg?
            /** @var GoToProductRepository $citrixProductRepository */
            $citrixProductRepository = $this->em->getRepository(GoToProduct::class);
            foreach ($contactsToAdd as $email => $info) {
                if (!isset($leads[strtolower($email)])) {
                    $lead = (new Lead())
                        ->addUpdatedField('email', $info['email'])
                        ->addUpdatedField('firstname', $info['firstname'])
                        ->addUpdatedField('lastname', $info['lastname']);
                    $this->leadModel->saveEntity($lead);

                    $leads[strtolower($email)] = $lead;
                }

                $citrixEvent = new GoToEvent();
                $citrixEvent->setGoToProduct($citrixProductRepository->findOneByProductKey($productKey));
                $citrixEvent->setEventType($eventType);
                $citrixEvent->setContact($leads[$email]);

                if (!empty($info['event_date'])) {
                    $citrixEvent->setEventDate($info['event_date']);
                }

                if (!empty($info['joinUrl'])) {
                    $citrixEvent->setJoinUrl($eventName.'_!'.$info['joinUrl']);
                }

                $newEntities[] = $citrixEvent;

                if (null !== $output) {
                    $output->writeln(
                        ' + '.$email.' '.$eventType.' to '.
                        substr($citrixEvent->getGoToProduct()->getName(), 0, 40).((strlen(
                            $citrixEvent->getGoToProduct()->getName()
                        ) > 40) ? '...' : '.')
                    );
                }

                ++$count;
            }

            $this->getRepository()->saveEntities($newEntities);
        }

        // Delete events
        if ([] !== $emailsToRemove) {
            $citrixProductRepository = $this->em->getRepository(GoToProduct::class);
            $citrixEvents            = $this->getRepository()->findAllByMailAndEvent($emailsToRemove, $productKey);
            /** @var GoToEvent $citrixEvent */
            foreach ($citrixEvents as $citrixEvent) {
                if (null !== $output) {
                    $output->writeln(
                        ' - '.$citrixEvent->getContact()->getEmail().' '.$eventType.' from '.
                        substr($citrixEvent->getGoToProduct()->getName(), 0, 40).((strlen(
                            $citrixEvent->getGoToProduct()->getName()
                        ) > 40) ? '...' : '.')
                    );
                }

                ++$count;
            }

            $this->getRepository()->deleteEntities($citrixEvents);
        }

        if ([] !== $newEntities) {
            /** @var GoToEvent $entity */
            foreach ($newEntities as $entity) {
                if ($this->dispatcher->hasListeners(GoToEvents::ON_GOTO_EVENT_UPDATE)) {
                    $citrixEvent = new GoToEventUpdateEvent($product, $eventName, $productKey, $eventType,
                        $entity->getLead());
                    $this->dispatcher->dispatch(GoToEvents::ON_GOTO_EVENT_UPDATE, $citrixEvent);
                    unset($citrixEvent);
                }
            }
        }

        $this->em->clear(Lead::class);
        $this->em->clear(GoToEvent::class);

        return $count;
    }

    /**
     * @param $found
     * @param $known
     *
     * @return array
     */
    private function filterEventContacts($found, $known)
    {
        // Lowercase the emails to keep things consistent
        $known  = array_map('strtolower', $known);
        $delete = array_diff($known, array_map('strtolower', array_keys($found)));
        $add    = array_filter(
            $found,
            static fn ($key) => !in_array(strtolower($key), $known),
            ARRAY_FILTER_USE_KEY
        );

        return [$add, $delete];
    }

    /**
     * @param $productType
     * @param $product
     * @param OutputInterface $output
     */
    public function syncProduct($productType, $product, $output = null)
    {
        /** @var GoToProductRepository $productRepository */
        $productRepository = $this->em->getRepository(GoToProduct::class);

        /** @var GoToProduct $persistedProduct */
        $persistedProduct = $productRepository->findOneBy([
            'product_key' => $product[$productType.'Key'],
            'product'     => $productType,
        ]);
        if (null === $persistedProduct) {
            $persistedProduct = new GoToProduct();
        }

        $persistedProduct->setName($product['subject']);
        $persistedProduct->setProduct($productType);
        $persistedProduct->setProductKey($product[$productType.'Key']);
        $persistedProduct->setOrganizerKey($product['organizerKey']);
        $persistedProduct->setLanguage($product['locale']);

        if (array_key_exists('recurrenceKey', $product)) {
            $persistedProduct->setRecurrenceKey($product['recurrenceKey']);
        }

        if (array_key_exists('times', $product)) {
            try {
                $persistedProduct->setDate(new \DateTime($product['times'][0]['startTime']));
                $persistedProduct->getDate()->setTimezone(new \DateTimeZone($product['timeZone']));
                $persistedProduct->setDuration(strtotime($product['times'][0]['endTime']) - strtotime($product['times'][0]['startTime']));
            } catch (\Exception $exception) {
                $output->writeln('Invalid Date Format');
            }
        }

        if (array_key_exists('description', $product)) {
            $persistedProduct->setDescription($product['description']);
        }

        $persistedProduct->setAuthor(null);
        $panelist = $this->goToHelper->getPanelists($productType, $product['organizerKey'], $product[$productType.'Key']);
        if (!empty($panelist)) {
            foreach ($panelist as $author) {
                if (empty($persistedProduct->getAuthor())) {
                    $persistedProduct->setAuthor($author['name']);
                } else {
                    $persistedProduct->setAuthor($persistedProduct->getAuthor().', '.$author['name']);
                }
            }
        }

        $persistedProduct->setStatus(STATUS_ACTIVE);
        $productRepository->saveEntity($persistedProduct);
    }

    public function getProducts($product_name, $from = null, $to = null, $reduceSessions = false, $withDetails = null)
    {
        $cpr      = $this->em->getRepository(GoToProduct::class);
        $products = $cpr->getCitrixChoices(true, $reduceSessions);
        uasort($products, static function ($a1, $a2) {
            $v1 = strtotime($a1['date']['date']);
            $v2 = strtotime($a2['date']['date']);

            return $v1 - $v2;
        });
        if (!$withDetails) {
            foreach ($products as $key => $product) {
                $products[$key] = $product['name'];
            }
        }

        return $products;
    }

    public function getIdByNameAndDate($name, $date)
    {
        $productRepository = $this->em->getRepository(GoToProduct::class);
        $result            = $productRepository->findOneBy(['name' => $name, 'date' => $date]);

        return $result ? $result->getId() : null;
    }

    public function getProductById($id)
    {
        $cpr = $this->em->getRepository(GoToProduct::class);

        return $cpr->findOneByProductKey($id);
    }
}
