<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticGoToBundle\Entity;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\TimelineTrait;
use MauticPlugin\MauticCrmBundle\Integration\Salesforce\Object\Contact;

class GoToEventRepository extends CommonRepository
{
    use TimelineTrait;

    /**
     * Fetch the base event data from the database.
     *
     * @param string $product
     * @param string $eventType
     * @param \DateTime $fromDate
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function getEvents($product, $eventType, \DateTime $fromDate = null)
    {
        $q = $this->createQueryBuilder('c');

        $expr = $q->expr()->andX(
            $q->expr()->eq('c.product', ':product'),
            $q->expr()->eq('c.event_type', ':eventType')
        );

        if ($fromDate) {
            $expr->add(
                $q->expr()->gte('c.event_date', ':fromDate')
            );
            $q->setParameter('fromDate', $fromDate);
        }

        $q->where($expr)
            ->setParameter('eventType', $eventType)
            ->setParameter('product', $product);

        return $q->getQuery()->getArrayResult();
    }

    /**
     * @param       $product
     * @param null $leadId
     * @param array $options
     *
     * @return array
     */
    public function getEventsForTimeline($product, $leadId = null, array $options = [])
    {
        $eventType = null;
        if (is_array($product)) {
            list($product, $eventType) = $product;
        }

        $query = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX . 'plugin_goto_events', 'c')
            ->leftJoin('c', 'plugin_goto_products', 'cp', 'c.citrix_product_id = cp.id')
            ->select('c.*');

        $query->where(
            $query->expr()->eq('cp.product', ':product')
        )
            ->setParameter('product', $product);

        if ($eventType) {
            $query->andWhere(
                $query->expr()->eq('c.event_type', ':type')
            )
                ->setParameter('type', $eventType);
        }

        if ($leadId) {
            $query->andWhere('c.contact_id = ' . (int)$leadId);
        }

        if (isset($options['search']) && $options['search']) {
            $query->andWhere($query->expr()->orX(
                $query->expr()->like('c.event_name', $query->expr()->literal('%' . $options['search'] . '%')),
                $query->expr()->like('c.product', $query->expr()->literal('%' . $options['search'] . '%'))
            ));
        }
        $testquery = $query->getSQL();
        return $this->getTimelineResults($query, $options, 'cp.product', 'c.event_date', [], ['event_date']);
    }

    /**
     * @param string $product
     * @param string $email
     *
     * @return array
     */
    public function findByEmail($product, $email)
    {

        return $this->findBy(
            [
                'product' => $product,
                'email' => $email,
            ]
        );
    }

    /**
     * @param string $product
     * @param string $email
     *
     * @return array
     */
    public function findRegisteredByEmail($product, $email)
    {
        $query = $this->createQueryBuilder('c')
            ->innerJoin(GoToProduct::class, 'cp', Join::WITH, 'c.citrixProduct = cp.id')
            ->innerJoin(Lead::class, 'l', Join::WITH, 'c.contact = l.id')
            ->select('c');
        $query->where(
            $query->expr()->eq('l.email', ':email')
        )->setParameter('email', $email);

        return $query->getQuery()->getResult();
    }

    public function findAllByMailAndEvent($email, $eventKey)
    {
        $contactRepository = $this->_em->getRepository(Lead::class);
        $contacts = $contactRepository->findBy(['email' => $email]);
        $productRepository = $this->_em->getRepository(GoToProduct::class);
        $product = $productRepository->findOneByProductKey($eventKey);
        return $this->findBy(
            [
                'contact' => $contacts,
                'citrixProduct' => $product
            ]
        );
    }


    /**
     * Get a list of entities.
     *
     * @param array $args
     *
     * @return Paginator
     */
    public function getEntities(array $args = [])
    {
        $alias = $this->getTableAlias();

        $q = $this->_em
            ->createQueryBuilder()
            ->select($alias)
            ->from('GoToEvent', $alias, $alias . '.id');

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     * @param                                                              $filter
     *
     * @return array
     */
    protected function addCatchAllWhereClause($q, $filter)
    {
        return $this->addStandardCatchAllWhereClause($q, $filter,
            ['c.product', 'c.email', 'c.eventType', 'c.eventName']);
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     * @param                                                              $filter
     *
     * @return array
     */
    protected function addSearchCommandWhereClause($q, $filter)
    {
        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }

    /**
     * @return array
     */
    public function getSearchCommands()
    {
        return $this->getStandardSearchCommands();
    }

    /**
     * @return array
     */
    protected function getDefaultOrder()
    {
        return [
            [$this->getTableAlias() . '.eventDate', 'ASC'],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTableAlias()
    {
        return 'c';
    }
}
