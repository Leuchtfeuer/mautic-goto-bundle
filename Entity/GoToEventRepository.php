<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Entity;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\TimelineTrait;

class GoToEventRepository extends CommonRepository
{
    use TimelineTrait;

    /**
     * Fetch the base event data from the database.
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function getEvents(string $product, string $eventType, \DateTime $fromDate = null)
    {
        $q = $this->createQueryBuilder('c');

        $expr = $q->expr()->andX(
            $q->expr()->eq('c.product', ':product'),
            $q->expr()->eq('c.event_type', ':eventType')
        );

        if (null !== $fromDate) {
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
     * @param mixed      $product
     * @param int|null   $leadId
     * @param mixed[]    $options
     *
     * @return mixed[]
     */
    public function getEventsForTimeline($product, int $leadId = null, array $options = [])
    {
        $eventType = null;
        if (is_array($product)) {
            [$product, $eventType] = $product;
        }

        $query = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.'plugin_goto_events', 'c')
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
            $query->andWhere('c.contact_id = '.(int) $leadId);
        }

        if (isset($options['search']) && $options['search']) {
            $query->andWhere($query->expr()->orX(
                $query->expr()->like('c.event_name', $query->expr()->literal('%'.$options['search'].'%')),
                $query->expr()->like('c.product', $query->expr()->literal('%'.$options['search'].'%'))
            ));
        }

        $query->getSQL();

        return $this->getTimelineResults($query, $options, 'cp.product', 'c.event_date', [], ['event_date']);
    }

    /**
     * @return mixed[]
     */
    public function findByEmail(string $product, string $email): array
    {
        return $this->findBy(
            [
                'product' => $product,
                'email'   => $email,
            ]
        );
    }

    /**
     * @return mixed[]
     */
    public function findRegisteredByEmail(string $product, string $email): array
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

    /**
     * @param mixed[] $email
     *
     * @return array|object[]
     */
    public function findAllByMailAndEvent($email, string $eventKey): array
    {
        $contactRepository = $this->getEntityManager()->getRepository(Lead::class);
        $contacts          = $contactRepository->findBy(['email' => $email]);
        $productRepository = $this->getEntityManager()->getRepository(GoToProduct::class);
        $product           = $productRepository->findOneByProductKey($eventKey);

        return $this->findBy(
            [
                'contact'       => $contacts,
                'citrixProduct' => $product,
            ]
        );
    }

    /**
     * Get a list of entities.
     *
     * @param mixed[] $args
     */
    public function getEntities(array $args = []): Paginator
    {
        $alias = $this->getTableAlias();

        $q = $this->getEntityManager()
            ->createQueryBuilder()
            ->select($alias)
            ->from('GoToEvent', $alias, $alias.'.id');

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * @param QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $qb
     * @param mixed                                          $filter
     */
    protected function addCatchAllWhereClause($qb, $filter): array
    {
        return $this->addStandardCatchAllWhereClause($qb, $filter,
            ['c.product', 'c.email', 'c.eventType', 'c.eventName']);
    }

    /**
     * @param QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     * @param mixed                                          $filter
     */
    protected function addSearchCommandWhereClause($q, $filter): array
    {
        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }

    public function getSearchCommands(): array
    {
        return $this->getStandardSearchCommands();
    }

    /**
     * @return array<int, int|string>
     */
    protected function getDefaultOrder(): array
    {
        return [
            [$this->getTableAlias().'.eventDate', 'ASC'],
        ];
    }

    public function getTableAlias(): string
    {
        return 'c';
    }
}
