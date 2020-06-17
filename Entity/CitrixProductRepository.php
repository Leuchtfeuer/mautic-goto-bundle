<?php


namespace MauticPlugin\MauticCitrixBundle\Entity;


use Mautic\CoreBundle\Entity\CommonRepository;

class CitrixProductRepository extends CommonRepository
{
    public function findByProductId($id)
    {
        return $this->findBy(['product_id' => $id]);
    }
}