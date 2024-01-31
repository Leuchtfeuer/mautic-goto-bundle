<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMInvalidArgumentException;
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

use const MauticPlugin\LeuchtfeuerGoToBundle\Entity\STATUS_ACTIVE;

use MauticPlugin\LeuchtfeuerGoToBundle\Event\GoToEventUpdateEvent;
use MauticPlugin\LeuchtfeuerGoToBundle\GoToEvents;
use MauticPlugin\LeuchtfeuerGoToBundle\Helper\GoToHelper;
use MauticPlugin\LeuchtfeuerGoToBundle\Helper\GoToProductTypes;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class GoToModel.
 */
class GoToModel extends FormModel
{
    /**
     * GoToModel constructor.
     */
    public function __construct(
        protected LeadModel $leadModel,
        protected EventModel $eventModel,
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
     * @throws ORMInvalidArgumentException
     * @throws OptimisticLockException
     * @throws \InvalidArgumentException
     * @throws ServiceCircularReferenceException
     * @throws ServiceNotFoundException
     */
    public function addEvent(string $product, string $email, string $eventName, string $eventDesc, string $eventType, Lead $lead, \DateTime $eventDate = null): void
    {
        if (!GoToProductTypes::isValidValue($product) || !GoToEventTypes::isValidValue($eventType)) {
            $this->goToHelper->log('addEvent: incorrect data');

            return;
        }

        $productRepository = $this->getProductRepository();
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
     * @return mixed[]
     */
    public function getEventsByLeadEmail(string $product, string $email): array
    {
        if (!GoToProductTypes::isValidValue($product)) {
            return []; // is not a valid citrix product
        }

        return $this->getRepository()->findByEmail($product, $email);
    }

    /**
     * @return mixed[]
     */
    public function getEmailsByEvent(string $product, string $productId, string $eventType): array
    {
        /** @var GoToProductRepository $productRepository */
        $productRepository = $this->getProductRepository();

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
        if (0 !== (!is_countable($goToEvents) ? 0 : count($goToEvents))) {
            $emails = array_map(
                static fn (GoToEvent $citrixEvent) => $citrixEvent->getContact()->getEmail(),
                $goToEvents
            );
        }

        return $emails;
    }

    /**
     * @return mixed[]
     */
    public function getDistinctEventNames(string $product): array
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
     * @return mixed[]
     */
    public function getDistinctEventNamesDesc(string $product): array
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
     * @param mixed[] $eventNames
     */
    public function countEventsBy(string $product, string $email, string $eventType, array $eventNames = []): int
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
        foreach ($eventNames as $key=>$event) {
            $query->setParameter(':event'.$key, '%'.$event.'%');
        }

        $query->getSQL();

        return (int) $query->getResult()[0]['cant'];
    }

    public function syncEvent(string $product, string $productId, string $eventName, string $eventDesc, int &$count = 0, OutputInterface $output = null): void
    {
        $cpr = $this->getProductRepository();
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
     * @param mixed[] $contactsToAdd
     * @param mixed[] $emailsToRemove
     *
     * @throws ORMInvalidArgumentException
     * @throws OptimisticLockException
     * @throws \InvalidArgumentException
     * @throws ServiceCircularReferenceException
     * @throws ServiceNotFoundException
     */
    public function batchAddAndRemove(
        string $product,
        string $eventName,
        string $productKey,
        string $eventType,
        array $contactsToAdd = [],
        array $emailsToRemove = [],
        OutputInterface $output = null
    ): int {
        if (!GoToProductTypes::isValidValue($product) || !GoToEventTypes::isValidValue($eventType)) {
            return 0;
        }

        $count       = 0;
        $newEntities = [];

        // Add events
        if ([] !== $contactsToAdd) {
            $searchEmails = array_keys($contactsToAdd);
            /** @phpstan-ignore-next-line  */
            $leads        = $this->leadModel->getRepository()->getLeadsByFieldValue('email', $searchEmails, null, true);
            // todo give as arg?
            /** @var GoToProductRepository $citrixProductRepository */
            $citrixProductRepository = $this->getProductRepository();
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
            $citrixProductRepository = $this->getProductRepository();
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

        /** @var GoToEvent $entity */
        foreach ($newEntities as $entity) {
            if ($this->dispatcher->hasListeners(GoToEvents::ON_GOTO_EVENT_UPDATE)) {
                $citrixEvent = new GoToEventUpdateEvent($product, $eventName, $productKey, $eventType, $entity->getContact());
                $this->dispatcher->dispatch($citrixEvent, GoToEvents::ON_GOTO_EVENT_UPDATE);
                unset($citrixEvent);
            }
        }

        $this->em->clear();
        $this->em->clear();

        return $count;
    }

    /**
     * @return mixed[]
     */
    private function filterEventContacts(mixed $found, mixed $known): array
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
     * @param mixed[] $product
     */
    public function syncProduct(string $productType, array $product, OutputInterface $output = null): void
    {
        /** @var GoToProductRepository $productRepository */
        $productRepository = $this->getProductRepository();

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
                $persistedProduct->setDuration((string) (strtotime($product['times'][0]['endTime']) - strtotime($product['times'][0]['startTime'])));
            } catch (\Exception) {
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

    /**
     * @return mixed[]
     */
    public function getProducts(string $product_name, \DateTime $from = null, \DateTime $to = null, bool $reduceSessions = false, bool $withDetails = null): array
    {
        $cpr      = $this->getProductRepository();
        $products = $cpr->getCitrixChoices(true, $reduceSessions);
        uasort($products, static function ($a1, $a2): int {
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

    public function getIdByNameAndDate(string $name, string $date): int|null
    {
        $productRepository = $this->getProductRepository();
        $result            = $productRepository->findOneBy(['name' => $name, 'date' => $date]);

        return $result ? $result->getId() : null;
    }

    public function getProductById(mixed $id): ?GoToProduct
    {
        $cpr = $this->getProductRepository();

        return $cpr->findOneByProductKey((string) $id);
    }

    public function getProductRepository(): GoToProductRepository
    {
        return $this->em->getRepository(GoToProduct::class);
    }

    public function getProductRepository(): GoToProductRepository
    {
        return $this->em->getRepository(GoToProduct::class);
    }
}
