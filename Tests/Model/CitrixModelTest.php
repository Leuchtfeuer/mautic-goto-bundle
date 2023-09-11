<?php

namespace MauticPlugin\LeuchtfeuerGoToBundle\Tests\Model;

use MauticPlugin\LeuchtfeuerGoToBundle\Model\GoToModel;
use PHPUnit\Framework\TestCase;

class CitrixModelTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getMauticFixtures($returnClassNames = false)
    {
        $fixtures    = [];
        $fixturesDir = __DIR__.'/../DataFixtures/ORM';

        if (file_exists($fixturesDir)) {
            $classPrefix = 'MauticPlugin\\LeuchtfeuerGoToBundle\\Tests\\DataFixtures\\ORM\\';
            $this->populateFixturesFromDirectory($fixturesDir, $fixtures, $classPrefix, $returnClassNames);
        }

        return $fixtures;
    }

    public function testCountEventsBy()
    {
        /** @var GoToModel $model */
        $model = $this->container->get(GoToModel::class);
        $count = $model->countEventsBy('webinar', "joe.o'connor@domain.com", 'registered', ['sample-webinar_#0000']);
        $this->assertEquals($count, 1);
    }
}
