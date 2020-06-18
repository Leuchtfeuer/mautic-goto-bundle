<?php


namespace MauticPlugin\MauticCitrixBundle\Entity;


use Mautic\CoreBundle\Entity\CommonRepository;

class CitrixProductRepository extends CommonRepository
{
    /**
     * @param $key
     * @return CitrixProduct|null
     */
    public function findOneByProductKey($key)
    {
        return $this->findOneBy(['product_key' => $key]);
    }

    public function findSessionsByRecurrenceKey(){

    }

    public function getAllNonRecurringProducts(){
        return $this->findBy(['recurrence_key' => null]);
    }
}