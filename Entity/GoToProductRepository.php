<?php


namespace MauticPlugin\MauticGoToBundle\Entity;


use Mautic\CoreBundle\Entity\CommonRepository;
use MauticPlugin\MauticGoToBundle\Helper\GoToHelper;
use MauticPlugin\MauticCrmBundle\Integration\Salesforce\QueryBuilder;

class GoToProductRepository extends CommonRepository
{
    /**
     * @param $key
     * @return GoToProduct|null
     */
    public function findOneByProductKey($key)
    {
        return $this->findOneBy(['product_key' => $key]);
    }

    public function findSessionsByRecurrenceKey(){

    }

    public function getById($id)
    {
        return $this->find($id);
    }

    public function getAllNonRecurringProducts(){
        return $this->findBy(['recurrence_key' => null]);
    }

    public function getCitrixChoices($onlyFutures = true, $reduceSessions = true){
        if($onlyFutures){
            $results = $this->getFutureProducts();
        } else {
            $results = $this->getProductsBetweenSpecificDates();
        }
        $key = 'product_key';
        $return_results = [];
        /**
         * @var array $results
         * @var GoToProduct $result
         */
        if($reduceSessions){
            $recurrenceKeyTemp = '';
            foreach ($results as $result){
                $recurrenceKeyTemp = $result->getRecurrenceKey();
                foreach ($results as $key => $session){
                    $diff = $session->getDate()->getTimestamp() - $result->getDate()->getTimestamp();
                    if($recurrenceKeyTemp !== null && $session->getRecurrenceKey() === $recurrenceKeyTemp && ($diff > 0)){
                        unset($results[$key]);
                    }
                }
            }
        }
        foreach ($results as $result) {
            $return_results[$result->getProductKey()] = json_decode(json_encode($result), true);
        }
        return $return_results;

    }

    public function getFutureProducts(){

        return $this->getProductsBetweenSpecificDates(new \DateTime('now'));

    }



    /**
     * @param \DateTime $from
     * @param \DateTime $to
     * @throws \Exception
     */
    public function getProductsBetweenSpecificDates($from = null, $to = null){

        if($to === null){
            $to = new \DateTime('now + 50 years');
        }
        if($from === null){
            $from = new \DateTime('now - 10 years');
        }

        $qb = $this->createQueryBuilder("e");
        $qb
            ->andWhere('e.date BETWEEN :from AND :to')
            ->setParameter('from', $from )
            ->setParameter('to', $to)
        ;
        $result = $qb->getQuery()->getResult();
        return $result;
    }

    public function reduceSessionsToWebinar($sessions)
    {
        $key = "";
        $temp_array = array();
        $i = 0;
        $key_array = array();

        foreach($sessions as $session) {
            if (!in_array($session[$key], $key_array)) {
                $key_array[$i] = $session[$key];
                $temp_array[$i] = $session;
            }
            $i++;
        }
        return $temp_array;
    }
}