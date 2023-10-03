<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\LeuchtfeuerGoToBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\DataFixtures\ORM\LoadRoleData;
use Mautic\UserBundle\DataFixtures\ORM\LoadUserData;
use MauticPlugin\LeuchtfeuerGoToBundle\Model\GoToModel;
use MauticPlugin\LeuchtfeuerGoToBundle\Tests\CreateEntities;
use MauticPlugin\LeuchtfeuerGoToBundle\Tests\DataFixtures\ORM\LoadCitrixData;

class CitrixModelTest extends MauticMysqlTestCase
{
    use CreateEntities;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtures = $this->loadFixtures(
            [
                LoadRoleData::class,
                LoadUserData::class,
                LoadCitrixData::class
            ],
            false
        )->getReferenceRepository();

        $this->createIntegration();
    }

    protected function beforeBeginTransaction(): void
    {
        $this->resetAutoincrement(
            [
                'leads',
                'lead_lists',
                'users',
            ]
        );
    }

    public function testCountEventsBy()
    {
        /** @var GoToModel $model */
        $model = self::$container->get('mautic.citrix.model.citrix');
        $count = $model->countEventsBy('webinar', "joe.o'connor@domain.com", 'registered', ['sample-webinar_#0000']);


        $this->assertEquals(1, $count);
    }
}
