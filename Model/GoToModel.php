<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticGoToBundle\Model;

use Mautic\CampaignBundle\Executioner\Scheduler\Mode\DateTime;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\MauticGoToBundle\GoToEvents;
use MauticPlugin\MauticGoToBundle\Entity\GoToEvent;
use MauticPlugin\MauticGoToBundle\Entity\GoToEventTypes;
use MauticPlugin\MauticGoToBundle\Entity\GoToProduct;
use MauticPlugin\MauticGoToBundle\Entity\GoToProductRepository;
use MauticPlugin\MauticGoToBundle\Event\GoToEventUpdateEvent;
use MauticPlugin\MauticGoToBundle\Helper\GoToHelper;
use MauticPlugin\MauticGoToBundle\Helper\GoToProductTypes;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GoToModel.
 */
class GoToModel extends FormModel
{
    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var EventModel
     */
    protected $eventModel;

    /**
     * GoToModel constructor.
     *
     * @param LeadModel $leadModel
     * @param EventModel $eventModel
     */
    public function __construct(LeadModel $leadModel, EventModel $eventModel)
    {
        $this->leadModel = $leadModel;
        $this->eventModel = $eventModel;
    }

    /**
     * {@inheritdoc}
     *
     * @return \MauticPlugin\MauticGoToBundle\Entity\GoToEventRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(GoToEvent::class);
    }

    /**
     * @param string $product
     * @param string $email
     * @param string $eventName
     * @param string $eventDesc
     * @param Lead $lead
     * @param string $eventType
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
            GoToHelper::log('addEvent: incorrect data');

            return;
        }
        //todo rework
        $productRepository = $this->em->getRepository(GoToProduct::class);
        $productRepository->findOneBy($product);
        $goToProduct = new GoToProduct();
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
                'eventType' => $eventType,
            ]
        );

        $emails = [];
        if (0 !== count($goToEvents)) {
            $emails = array_map(
                static function (GoToEvent $citrixEvent) {
                    return $citrixEvent->getContact()->getEmail();
                },
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
            "SELECT DISTINCT(c.eventName) FROM MauticGoToBundle:GoToEvent c WHERE c.product='%s'",
            $product
        );
        $query = $this->em->createQuery($dql);
        $items = $query->getResult();

        return array_map(
            function ($item) {
                return array_pop($item);
            },
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
            "SELECT DISTINCT c.product_key, c.name, c.date FROM MauticGoToBundle:GoToProduct c WHERE c.product='%s'",
            $product
        );
        $query = $this->em->createQuery($dql);
        $items = $query->getResult();
        $result = [];
        foreach ($items as $item) {
            $eventDate = $item['date'];

            $result["ID:" . $item['product_key']] = $eventDate->format('d.m.Y H:i') . ' ' . $item['name'];
        }

        return $result;
    }

    public function getDistinctEvenNamesDescForCampaign($product)
    {
        $productArray = $this->model->getProducts($product, new \DateTime('now'), false, false, false);
        return array_combine($productArray, $productArray);
    }

    /**
     * @param string $product
     * @param string $email
     * @param string $eventType
     * @param array $eventNames
     *
     * @return int
     */
    public function countEventsBy($product, $email, $eventType, array $eventNames = [])
    {
        if (!GoToProductTypes::isValidValue($product) || !GoToEventTypes::isValidValue($eventType)) {
            return 0; // is not a valid citrix product
        }
        $dql = 'SELECT COUNT(c.id) as cant FROM MauticGoToBundle:GoToEvent c ' .
            ' WHERE c.product=:product and c.email=:email AND c.eventType=:eventType ';

        if (0 !== count($eventNames)) {
            $dql .= 'AND c.eventName IN (:eventNames)';
        }

        $query = $this->em->createQuery($dql);
        $query->setParameters([
            ':product' => $product,
            ':email' => $email,
            ':eventType' => $eventType,
        ]);
        if (0 !== count($eventNames)) {
            $query->setParameter(':eventNames', $eventNames);
        }

        return (int)$query->getResult()[0]['cant'];
    }

    /**
     * @param      $product
     * @param      $productId
     * @param      $eventName
     * @param      $eventDesc
     * @param int $count
     * @param null $output
     */
    public function syncEvent($product, $productId, $eventName, $eventDesc, &$count = 0, $output = null)
    {
        $cpr = $this->em->getRepository(GoToProduct::class);
        /** @var GoToProduct $product_result */
        $product_result = $cpr->findOneBy(['product_key' => $productId]);
        $organizerKey = $product_result->getOrganizerKey();
        $registrants = GoToHelper::getRegistrants($product, $productId, $organizerKey);
        $knownRegistrants = $this->getEmailsByEvent(
            $product,
            $productId,
            GoToEventTypes::REGISTERED
        );

        list($registrantsToAdd, $registrantsToDelete) = $this->filterEventContacts($registrants, $knownRegistrants);
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

        $attendees = GoToHelper::getAttendees($product, $productId, $organizerKey);
        $knownAttendees = $this->getEmailsByEvent(
            $product,
            $eventName,
            GoToEventTypes::ATTENDED
        );

        list($attendeesToAdd, $attendeesToDelete) = $this->filterEventContacts($attendees, $knownAttendees);
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
     * @param string $product
     * @param string $eventName
     * @param string $productKey
     * @param string $eventType
     * @param array $contactsToAdd
     * @param array $emailsToRemove
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

        $count = 0;
        $newEntities = [];

        // Add events
        if (0 !== count($contactsToAdd)) {
            $searchEmails = array_keys($contactsToAdd);
            $leads = $this->leadModel->getRepository()->getLeadsByFieldValue('email', $searchEmails, null, true);
            //todo give as arg?
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
                    $citrixEvent->setJoinUrl($eventName . '_!' . $info['joinUrl']);
                }

                $newEntities[] = $citrixEvent;

                if ($output !== null) {
                    $output->writeln(
                        ' + ' . $email . ' ' . $eventType . ' to ' .
                        substr($citrixEvent->getGoToProduct()->getName(), 0, 40) . ((strlen(
                                $citrixEvent->getGoToProduct()->getName()
                            ) > 40) ? '...' : '.')
                    );
                }
                ++$count;
            }

            $this->getRepository()->saveEntities($newEntities);
        }

        // Delete events
        if (0 !== count($emailsToRemove)) {
            $citrixProductRepository = $this->em->getRepository(GoToProduct::class);
            $citrixEvents = $this->getRepository()->findAllByMailAndEvent($emailsToRemove, $productKey);
            /** @var GoToEvent $citrixEvent */
            foreach ($citrixEvents as $citrixEvent) {
                if (null !== $output) {
                    $output->writeln(
                        ' - ' . $citrixEvent->getContact()->getEmail() . ' ' . $eventType . ' from ' .
                        substr($citrixEvent->getGoToProduct()->getName(), 0, 40) . ((strlen(
                                $citrixEvent->getGoToProduct()->getName()
                            ) > 40) ? '...' : '.')
                    );
                }
                ++$count;
            }
            $this->getRepository()->deleteEntities($citrixEvents);
        }

        if (0 !== count($newEntities)) {
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
        $known = array_map('strtolower', $known);
        $delete = array_diff($known, array_map('strtolower', array_keys($found)));
        $add = array_filter(
            $found,
            function ($key) use ($known) {
                return !in_array(strtolower($key), $known);
            },
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

        if (array_key_exists($productType . "Key", $product)) {
            $productId = $productType . "Key";
        } else {
            $productId = $productType . "Id";
        }
        /** @var GoToProduct $persistedProduct */
        $persistedProduct = $productRepository->findOneBy([
            'product_key' => $product[$productId],
            'product' => $productType
        ]);
        if ($persistedProduct === null) {
            $persistedProduct = new GoToProduct();
        }
        if(array_key_exists('subject', $product)){
            $persistedProduct->setName($product['subject']);
        } else {
            $persistedProduct->setName($product['name']);
        }
        $persistedProduct->setProduct($productType);
        $persistedProduct->setProductKey($product[$productId]);
        if(array_key_exists('organizerKey', $product)){
            $persistedProduct->setOrganizerKey($product['organizerKey']);
        } else {
            $persistedProduct->setOrganizerKey($product['organizers'][0]['organizerKey']);
        }
        if (array_key_exists('locale', $product)){
            $persistedProduct->setLanguage($product['locale']);
        }
        if (array_key_exists('recurrenceKey', $product)) {
            $persistedProduct->setRecurrenceKey($product['recurrenceKey']);
        }

        if (array_key_exists('times', $product)) {
            try {
                if (array_key_exists('startTime', $product)){
                    $dateString = 'Time';
                } else {
                    $dateString = 'Date';
                }
                $persistedProduct->setDate(new \DateTime($product['times'][0]['start'.$dateString]));
                $persistedProduct->setDuration(strtotime($product['times'][0]['end'.$dateString]) - strtotime($product['times'][0]['start'.$dateString]));
            } catch (\Exception $e) {
                $output->writeln('Invalid Date Format');
            }
        }

        if (array_key_exists('startTime', $product)){
            try {
                $persistedProduct->setDate(new \DateTime($product['startTime']));
                $persistedProduct->setDuration(strtotime($product['endTime']) - strtotime($product['startTime']));
            } catch (\Exception $e) {
                $output->writeln('Invalid Date Format');
            }
        }

        if (array_key_exists('description', $product)) {
            $persistedProduct->setDescription($product['description']);
        }
        if ($productType === GoToProductTypes::GOTOWEBINAR) {
            $persistedProduct->setAuthor(null);
            $panelist = GoToHelper::getPanelists($productType, $product['organizerKey'], $product[$productId]);
            if (!empty($panelist)) {
                foreach ($panelist as $author) {
                    if (empty($persistedProduct->getAuthor())) {
                        $persistedProduct->setAuthor($author['name']);
                    } else {
                        $persistedProduct->setAuthor($persistedProduct->getAuthor() . ', ' . $author['name']);
                    }

                }
            }
        }

        $productRepository->saveEntity($persistedProduct);
    }

    public function getProducts($product_name, $from = null, $to = null, $reduceSessions = false, $withDetails = null)
    {
        $cpr = $this->em->getRepository(GoToProduct::class);
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

    public function getProductById($id)
    {
        $cpr = $this->em->getRepository(GoToProduct::class);
        return $cpr->findOneByProductKey($id);
    }

    public function deleteRemovedProducts(array $productIds)
    {
        $cpr = $this->em->getRepository(GoToProduct::class);
        $foundProducts = $cpr->findBy(["product_key" => $productIds]);
        $all = $cpr->findAll();
        foreach ($all as $key => $product) {
            $found = false;
            foreach ($foundProducts as $foundProduct) {
                $found = false;
                if ($product->getProductKey() === $foundProduct->getProductKey()) {
                    $found = true;
                    break;
                }
            }
            if ($found) {
                unset($all[$key]);
            }
        }
        $cpr->deleteEntities($all);
    }
}
