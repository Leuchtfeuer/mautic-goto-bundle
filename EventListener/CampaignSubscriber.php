<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticGoToBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use MauticPlugin\MauticGoToBundle\Form\Type\GoToCampaignActionType;
use MauticPlugin\MauticGoToBundle\Form\Type\GoToCampaignEventType;
use MauticPlugin\MauticGoToBundle\GoToEvents;
use MauticPlugin\MauticGoToBundle\Entity\GoToEventTypes;
use MauticPlugin\MauticGoToBundle\Helper\GoToHelper;
use MauticPlugin\MauticGoToBundle\Helper\GoToProductTypes;
use MauticPlugin\MauticGoToBundle\Model\GoToModel;

/**
 * Class CampaignSubscriber.
 */
class CampaignSubscriber extends CommonSubscriber
{
    use GoToRegistrationTrait;
    use GoToStartTrait;

    /**
     * @var GoToModel
     */
    private $goToModel;

    /**
     * CampaignSubscriber constructor.
     *
     * @param GoToModel $goToModel
     */
    public function __construct(GoToModel $goToModel)
    {
        $this->goToModel = $goToModel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD       => ['onCampaignBuild', 0],
            GoToEvents::ON_GOTO_WEBINAR_EVENT   => ['onWebinarEvent', 0],
            GoToEvents::ON_GOTO_MEETING_EVENT   => ['onMeetingEvent', 0],
            GoToEvents::ON_GOTO_TRAINING_EVENT  => ['onTrainingEvent', 0],
            GoToEvents::ON_GOTO_ASSIST_EVENT    => ['onAssistEvent', 0],
            GoToEvents::ON_GOTO_WEBINAR_ACTION  => ['onWebinarAction', 0],
            GoToEvents::ON_GOTO_MEETING_ACTION  => ['onMeetingAction', 0],
            GoToEvents::ON_GOTO_TRAINING_ACTION => ['onTrainingAction', 0],
            GoToEvents::ON_GOTO_ASSIST_ACTION   => ['onAssistAction', 0],
        ];
    }

    /* Actions */

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onWebinarAction(CampaignExecutionEvent $event)
    {
        $event->setResult($this->onCitrixAction(GoToProductTypes::GOTOWEBINAR, $event));
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onMeetingAction(CampaignExecutionEvent $event)
    {
        $event->setResult($this->onCitrixAction(GoToProductTypes::GOTOMEETING, $event));
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onTrainingAction(CampaignExecutionEvent $event)
    {
        $event->setResult($this->onCitrixAction(GoToProductTypes::GOTOTRAINING, $event));
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onAssistAction(CampaignExecutionEvent $event)
    {
        $event->setResult($this->onCitrixAction(GoToProductTypes::GOTOASSIST, $event));
    }

    /**
     * @param string                 $product
     * @param CampaignExecutionEvent $event
     *
     * @return bool
     */
    public function onCitrixAction($product, CampaignExecutionEvent $event)
    {
        if (!GoToProductTypes::isValidValue($product)) {
            return false;
        }

        // get firstName, lastName and email from keys for sender email
        $config   = $event->getConfig();
        $criteria = $config['event-criteria-'.$product];
        /** @var array $list */
        $list     = $config[$product.'-list'];
        $actionId = 'citrix.action.'.$product;
        try {
            $productlist = $this->goToModel->getProducts($product, new \DateTime('now'), false);
            $products    = [];

            foreach ($list as $productId) {
                if (array_key_exists(
                    $productId,
                    $productlist
                )) {
                    $products[] = [
                        'productId'    => $productId,
                        'productTitle' => $productlist[$productId],
                    ];
                }
            }
            if (in_array($criteria, ['webinar_register', 'training_register'], true)) {
                $this->registerProduct($product, $event->getLead(), $products);
            } else {
                if (in_array($criteria, ['assist_screensharing', 'training_start', 'meeting_start'], true)) {
                    $emailId = $config['template'];
                    $this->startProduct($product, $event->getLead(), $products, $emailId, $actionId);
                }
            }
        } catch (\Exception $ex) {
            GoToHelper::log('onCitrixAction - '.$product.': '.$ex->getMessage());
        }

        return true;
    }

    /* Events */

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onWebinarEvent(CampaignExecutionEvent $event)
    {
        $event->setResult($this->onCitrixEvent(GoToProductTypes::GOTOWEBINAR, $event));
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onMeetingEvent(CampaignExecutionEvent $event)
    {
        $event->setResult($this->onCitrixEvent(GoToProductTypes::GOTOMEETING, $event));
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onTrainingEvent(CampaignExecutionEvent $event)
    {
        $event->setResult($this->onCitrixEvent(GoToProductTypes::GOTOTRAINING, $event));
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onAssistEvent(CampaignExecutionEvent $event)
    {
        $event->setResult($this->onCitrixEvent(GoToProductTypes::GOTOASSIST, $event));
    }

    /**
     * @param string                 $product
     * @param CampaignExecutionEvent $event
     *
     * @return bool
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function onCitrixEvent($product, CampaignExecutionEvent $event)
    {
        if (!GoToProductTypes::isValidValue($product)) {
            return false;
        }

        $config   = $event->getConfig();
        $criteria = $config['event-criteria-'.$product];
        $list     = $config[$product.'-list'];
        $isAny    = in_array('ANY', $list, true);
        $email    = $event->getLead()->getEmail();

        if ('registeredToAtLeast' === $criteria) {
            $counter = $this->goToModel->countEventsBy(
                $product,
                $email,
                GoToEventTypes::REGISTERED,
                $isAny ? [] : $list
            );
        } else {
            if ('attendedToAtLeast' === $criteria) {
                $counter = $this->goToModel->countEventsBy(
                    $product,
                    $email,
                    GoToEventTypes::ATTENDED,
                    $isAny ? [] : $list
                );
            } else {
                return false;
            }
        }

        return $counter > 0;
    }

    /**
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        $activeProducts = [];
        foreach (GoToProductTypes::toArray() as $p) {
            if (GoToHelper::isAuthorized('Goto'.$p)) {
                $activeProducts[] = $p;
            }
        }
        if (0 === count($activeProducts)) {
            return;
        }

        $eventNames = [
            GoToProductTypes::GOTOWEBINAR  => GoToEvents::ON_GOTO_WEBINAR_EVENT,
            GoToProductTypes::GOTOMEETING  => GoToEvents::ON_GOTO_MEETING_EVENT,
            GoToProductTypes::GOTOTRAINING => GoToEvents::ON_GOTO_TRAINING_EVENT,
            GoToProductTypes::GOTOASSIST   => GoToEvents::ON_GOTO_ASSIST_EVENT,
        ];

        $actionNames = [
            GoToProductTypes::GOTOWEBINAR  => GoToEvents::ON_GOTO_WEBINAR_ACTION,
            GoToProductTypes::GOTOMEETING  => GoToEvents::ON_GOTO_MEETING_ACTION,
            GoToProductTypes::GOTOTRAINING => GoToEvents::ON_GOTO_TRAINING_ACTION,
            GoToProductTypes::GOTOASSIST   => GoToEvents::ON_GOTO_ASSIST_ACTION,
        ];

        foreach ($activeProducts as $product) {
            $event->addCondition(
                'citrix.event.'.$product,
                [
                    'label'           => 'plugin.citrix.campaign.event.'.$product.'.label',
                    'formType'        => GoToCampaignEventType::class,
                    'formTypeOptions' => [
                        'attr' => [
                            'data-product' => $product,
                        ],
                    ],
                    'eventName'      => $eventNames[$product],
                    'channel'        => 'citrix',
                    'channelNameField' => $product.'-list',
                ]
            );

            $event->addAction(
                'citrix.action.'.$product,
                [
                    'label'           => 'plugin.citrix.campaign.action.'.$product.'.label',
                    'formType'        => GoToCampaignActionType::class,
                    'formTypeOptions' => [
                        'attr' => [
                            'data-product' => $product,
                        ],
                    ],
                    'eventName'      => $actionNames[$product],
                    'channel'        => 'citrix',
                    'channelNameField' => $product.'-list',
                ]
            );
        }
    }
}
