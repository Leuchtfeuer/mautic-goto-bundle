<?php

namespace MauticPlugin\LeuchtfeuerGoToBundle\Entity;

use Doctrine\ORM\NonUniqueResultException;
use Mautic\CoreBundle\Entity\CommonRepository;
use function Doctrine\ORM\QueryBuilder;

class GoToProductRepository extends CommonRepository
{
    /**
     * @param $key
     *
     * @return GoToProduct|null
     */
    public function findOneByProductKey($key)
    {
        return $this->findOneBy(['product_key' => $key]);
    }

    public function findSessionsByRecurrenceKey()
    {
    }

    public function getById($id)
    {
        return $this->find($id);
    }

    public function getAllNonRecurringProducts()
    {
        return $this->findBy(['recurrence_key' => null]);
    }

    public function getCitrixChoices($onlyFutures = true, $reduceSessions = true)
    {
        $results        = $onlyFutures ? $this->getFutureProducts() : $this->getProductsBetweenSpecificDates();
        $key            = 'product_key';
        $return_results = [];
        /**
         * @var array       $results
         * @var GoToProduct $result
         */
        if ($reduceSessions) {
            $recurrenceKeyTemp = '';
            foreach ($results as $result) {
                $recurrenceKeyTemp = $result->getRecurrenceKey();
                foreach ($results as $key => $session) {
                    $diff = $result->getDate()->getTimestamp() - $session->getDate()->getTimestamp();
                    if (null !== $recurrenceKeyTemp && $session->getRecurrenceKey() === $recurrenceKeyTemp && ($diff > 0)) {
                        unset($results[$key]);
                    }
                }
            }
        }

        foreach ($results as $result) {
            $return_results[$result->getProductKey()] = json_decode(json_encode($result, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        }

        return $return_results;
    }

    public function getFutureProducts()
    {
        return $this->getProductsBetweenSpecificDates(new \DateTime('now'));
    }

    /**
     * @param \DateTime $from
     * @param \DateTime $to
     *
     * @throws \Exception
     */
    public function getProductsBetweenSpecificDates($from = null, $to = null)
    {
        if (null === $to) {
            $to = new \DateTime('now + 50 years');
        }

        if (null === $from) {
            $from = new \DateTime('now - 10 years');
        }

        $qb = $this->createQueryBuilder('e');
        $qb
            ->andWhere('e.date BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
        ;

        return $qb->getQuery()->getResult();
    }

    public function findOneByProductByNameAndDate(string $name, string $date): ?GoToProduct
    {

        $qb = $this->createQueryBuilder('e');
        $qb
            ->andWhere($qb->expr()->eq('e.name', ':name'))
            ->andWhere('e.date BETWEEN :from AND :to')
            ->setParameter('name', $name)
            ->setParameter('from', new \DateTime($date.':00'))
            ->setParameter('to', new \DateTime($date.':59'))
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function reduceSessionsToWebinar($sessions)
    {
        $key        = '';
        $temp_array = [];
        $i          = 0;
        $key_array  = [];

        foreach ($sessions as $session) {
            if (!in_array($session[$key], $key_array)) {
                $key_array[$i]  = $session[$key];
                $temp_array[$i] = $session;
            }

            ++$i;
        }

        return $temp_array;
    }
}
