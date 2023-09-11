<?php

namespace MauticPlugin\LeuchtfeuerGoToBundle\EventListener;

use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use MauticPlugin\LeuchtfeuerGoToBundle\Entity\GoToEvent;
use MauticPlugin\LeuchtfeuerGoToBundle\Event\TokenGenerateEvent;
use MauticPlugin\LeuchtfeuerGoToBundle\GoToEvents;
use MauticPlugin\LeuchtfeuerGoToBundle\Helper\GoToHelper;
use MauticPlugin\LeuchtfeuerGoToBundle\Helper\GoToProductTypes;
use MauticPlugin\LeuchtfeuerGoToBundle\Model\GoToModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class EmailSubscriber.
 */
class EmailSubscriber implements EventSubscriberInterface
{
    /**
     * @var GoToModel
     */
    protected $goToModel;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var TemplatingHelper
     */
    private $templating;

    private EventDispatcherInterface $dispatcher;

    /**
     * FormSubscriber constructor.
     */
    public function __construct(
        GoToModel $goToModel,
        TranslatorInterface $translator,
        TemplatingHelper $templating,
        EventDispatcherInterface $dispatcher
    ) {
        $this->goToModel   = $goToModel;
        $this->translator  = $translator;
        $this->templating  = $templating;
        $this->dispatcher  = $dispatcher;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            GoToEvents::ON_GOTO_TOKEN_GENERATE     => ['onTokenGenerate', 254],
            EmailEvents::EMAIL_ON_BUILD            => ['onEmailBuild', 0],
            EmailEvents::EMAIL_ON_SEND             => ['decodeTokensSend', 0],
            EmailEvents::EMAIL_ON_DISPLAY          => ['decodeTokensDisplay', 0],
        ];
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function onTokenGenerate(TokenGenerateEvent $event)
    {
        // inject product details in $event->params on email send
        if ('webinar' == $event->getProduct()) {
            $event->setProductLink('https://www.gotomeeting.com/webinar');
            $params = $event->getParams();
            if (!empty($params['lead'])) {
                $email  = $params['lead']['email'];
                $repo   = $this->goToModel->getRepository();
                $result = $repo->findRegisteredByEmail('webinar', $email);

                if ([] !== $result) {
                    /** @var GoToEvent $ce */
                    $ce = $result[0];
                    $event->setProductLink($ce->getJoinUrl());
                }
            } else {
                GoToHelper::log('Updating webinar token failed! Email not found '.implode(', ', $event->getParams()));
            }

            $event->setProductText($this->translator->trans('plugin.citrix.token.join_webinar'));
        }
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function onEmailBuild(EmailBuilderEvent $event)
    {
        // register tokens only if the plugins are enabled
        $tokens         = [];
        $activeProducts = [];
        foreach (['meeting', 'training', 'assist', 'webinar'] as $p) {
            if (GoToHelper::isAuthorized('Goto'.$p)) {
                $activeProducts[]          = $p;
                $tokens['{'.$p.'_button}'] = $this->translator->trans('plugin.citrix.token.'.$p.'_button');
                if ('webinar' === $p) {
                    $tokens['{'.$p.'_link}'] = $this->translator->trans('plugin.citrix.token.'.$p.'_link');
                }
            }
        }

        if ([] === $activeProducts) {
            return;
        }

        // register tokens
        if ($event->tokensRequested(array_keys($tokens))) {
            $event->addTokens(
                $event->filterTokens($tokens)
            );
        }
    }

    /**
     * Search and replace tokens with content.
     *
     * @throws \RuntimeException
     */
    public function decodeTokensDisplay(EmailSendEvent $event)
    {
        $this->decodeTokens($event, false);
    }

    /**
     * Search and replace tokens with content.
     *
     * @throws \RuntimeException
     */
    public function decodeTokensSend(EmailSendEvent $event)
    {
        $this->decodeTokens($event, true);
    }

    /**
     * Search and replace tokens with content.
     *
     * @param bool $triggerEvent
     *
     * @throws \RuntimeException
     */
    public function decodeTokens(EmailSendEvent $event, $triggerEvent = false)
    {
        $products = [
            GoToProductTypes::GOTOMEETING,
            GoToProductTypes::GOTOTRAINING,
            GoToProductTypes::GOTOASSIST,
            GoToProductTypes::GOTOWEBINAR,
        ];

        $tokens = [];
        foreach ($products as $product) {
            if (GoToHelper::isAuthorized('Goto'.$product)) {
                $params = [
                    'product'     => $product,
                    'productText' => '',
                    'productLink' => '',
                ];

                if ('webinar' == $product) {
                    $params['productText'] = $this->translator->trans('plugin.citrix.token.join_webinar');
                    $params['productLink'] = 'https://www.gotomeeting.com/webinar';
                }

                // trigger event to replace the links in the tokens
                if ($triggerEvent && $this->dispatcher->hasListeners(GoToEvents::ON_GOTO_TOKEN_GENERATE)) {
                    $params['lead'] = $event->getLead();
                    $tokenEvent     = new TokenGenerateEvent($params);
                    $this->dispatcher->dispatch(GoToEvents::ON_GOTO_TOKEN_GENERATE, $tokenEvent);
                    $params = $tokenEvent->getParams();
                    unset($tokenEvent);
                }

                $button = $this->templating->getTemplating()->render(
                    'LeuchtfeuerGoToBundle:SubscribedEvents\EmailToken:token.html.php',
                    $params
                );

                $tokens['{'.$product.'_link}']   = $params['productLink'];
                $tokens['{'.$product.'_button}'] = $button;
            }
        }

        $event->addTokens($tokens);
    }
}
