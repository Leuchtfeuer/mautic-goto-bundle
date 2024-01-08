<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

class GoToProductRepository extends CommonRepository
{
    public function findOneByProductKey(string $key): ?GoToProduct
    {
        return $this->findOneBy(['product_key' => $key]);
    }

    public function findSessionsByRecurrenceKey(): void
    {
    }

    public function getById(int $id): ?GoToProduct
    {
        return $this->find($id);
    }

    public function getAllNonRecurringProducts()
    {
        return $this->findBy(['recurrence_key' => null]);
    }

    /**
     * @return mixed[]
     *
     * @throws \Exception
     */
    public function getCitrixChoices(bool $onlyFutures = true, bool $reduceSessions = true): array
    {
        $results        = $onlyFutures ? $this->getFutureProducts() : $this->getProductsBetweenSpecificDates();
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

    /**
     * @throws \Exception
     */
    public function getFutureProducts()
    {
        return $this->getProductsBetweenSpecificDates(new \DateTime('now'));
    }

    /**
     * @throws \Exception
     */
    public function getProductsBetweenSpecificDates(\DateTime $from = null, \DateTime $to = null)
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
            ->setParameter('to', $to);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return mixed[]
     */
    public function reduceSessionsToWebinar($sessions): array
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
