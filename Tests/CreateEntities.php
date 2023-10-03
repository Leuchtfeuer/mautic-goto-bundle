<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Tests;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\Plugin;
use Mautic\PluginBundle\Helper\IntegrationHelper;

trait CreateEntities
{
    protected function createSegment($listConfig): LeadList
    {
        $list = new LeadList();
        $list->setName($listConfig['name']);
        $list->setPublicName($listConfig['name']);
        $list->setAlias($listConfig['alias']);
        $list->setIsGlobal($listConfig['public']);
        $list->setFilters($listConfig['filters']);

        $this->em->persist($list);

        return $list;
    }

    protected function createIntegration(): void
    {
        $plugin = new Plugin();
        $plugin->setName('GoTo Integration by Leuchtfeuer');
        $plugin->setDescription('Enables integration with Mautic supported GoTo collaboration products.');
        $plugin->setBundle('LeuchtfeuerGoToBundle');
        $plugin->setVersion('1.0');
        $plugin->setAuthor('Mautic');

        $this->em->persist($plugin);

        $webinar = new Integration();
        $webinar->setIsPublished(true);
        $webinar->setApiKeys([
            'app_name'      => 'Mautic',
            'client_id'     => 'client_id',
            'client_secret' => 'client_secret',
        ]);
        $webinar->setPlugin($plugin);
        $webinar->setName('GoTo Integration by Leuchtfeuer');

        $this->em->persist($webinar);
        $this->em->flush();

        /** @var IntegrationHelper $integrationHelper */
        $integrationHelper = self::$container->get('mautic.helper.integration');

        /** @var Integration $integration */
        $integrationObject = $integrationHelper->getIntegrationObject('Gotowebinar');

        $integrationObject->encryptAndSetApiKeys([
            'app_name'      => 'Mautic',
            'client_id'     => 'client_id',
            'client_secret' => 'client_secret',
        ], $webinar);

        $integrationObject->getIntegrationSettings()->setIsPublished(true);

        $this->em->flush();
    }
}
