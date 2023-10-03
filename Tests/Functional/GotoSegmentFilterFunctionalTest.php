<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\DataFixtures\ORM\LoadRoleData;
use Mautic\UserBundle\DataFixtures\ORM\LoadUserData;
use MauticPlugin\LeuchtfeuerGoToBundle\Tests\CreateEntities;
use MauticPlugin\LeuchtfeuerGoToBundle\Tests\DataFixtures\ORM\LoadCitrixData;

class GotoSegmentFilterFunctionalTest extends MauticMysqlTestCase
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

    public function testGotoSegments(): void
    {
        // Create Segment with filters
        $segment  = $this->createSegment([
            'name'     => 'Goto: Webinar Registration',
            'alias'    => 'goto-webinar-registration',
            'public'   => true,
            'filters'  => [
                [
                    'glue'       => 'and',
                    'type'       => 'email',
                    'object'     => 'lead',
                    'field'      => 'webinar-registration',
                    'operator'   => 'including',
                    'properties' => ['filter' => 'Any Webinar', 'display' => null],
                ],
            ],
        ]);

        $this->em->flush();
        $this->em->clear();

        // Build segments
        $command = $this->testSymfonyCommand(
            'mautic:segments:update',
            [
                '-i'    => $segment->getId(),
                '--env' => 'test',
            ]
        );

        $output = $command->getDisplay();
        $this->assertEquals(0, $command->getStatusCode());
        $this->assertStringContainsString('1 total contact(s) to be added in batches of 300', $output);
        $this->assertStringContainsString('1 contact(s) affected', $output);
    }
}
