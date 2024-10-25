<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\LeuchtfeuerGoToBundle\Helper\GoToHelper;
use MauticPlugin\LeuchtfeuerGoToBundle\Model\GoToModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PublicController extends CommonController
{
    public CoreParametersHelper $coreParametersHelper;

    /** @phpstan-ignore-next-line  */
    public function __construct(
        ManagerRegistry $doctrine,
        MauticFactory $factory,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        CoreParametersHelper $coreParametersHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        FlashBag $flashBag,
        ?RequestStack $requestStack,
        ?CorePermissions $security,

        private GoToHelper $goToHelper,
        private GoToModel $goToModel,
        private IntegrationHelper $integrationHelper,
    ) {
        parent::__construct($doctrine, $factory, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    /**
     * This proxy is used for the GoToTraining API requests in order to bypass the CORS restrictions in AJAX.
     *
     * @return mixed[]|JsonResponse|RedirectResponse|Response
     *
     * @throws AccessDeniedHttpException
     * @throws \InvalidArgumentException
     */
    public function proxyAction(Request $request)
    {
        $url = $request->query->get('url', null);
        if (!$url) {
            return $this->accessDenied(false, 'ERROR: url not specified');
        } else {
            $myIntegration     = $this->integrationHelper->getIntegrationObject('Gototraining');

            if (!$myIntegration || !$myIntegration->getIntegrationSettings()->getIsPublished()) {
                return $this->accessDenied(false, 'ERROR: GoToTraining is not enabled');
            }

            $ch = curl_init($url);
            if ('post' === strtolower($request->server->get('REQUEST_METHOD', ''))) {
                $headers = [
                    'Content-type: application/json',
                    'Accept: application/json',
                ];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request->request->all(), JSON_THROW_ON_ERROR));
            }

            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, $request->server->get('HTTP_USER_AGENT', ''));
            [$header, $contents]     = preg_split('#([\r\n][\r\n])\1#', curl_exec($ch), 2);
            $status                  = curl_getinfo($ch);
            curl_close($ch);
        }

        // Set the JSON data object contents, decoding it from JSON if possible.
        $decoded_json = json_decode($contents, null, 512, JSON_THROW_ON_ERROR);
        $data         = $decoded_json ?: $contents;

        // Generate JSON/JSONP string
        $json     = json_encode($data, JSON_THROW_ON_ERROR);
        $response = new Response($json, $status['http_code']);

        // Generate appropriate content-type header.
        $is_xhr = 'xmlhttprequest' === strtolower($request->server->get('HTTP_X_REQUESTED_WITH', null));
        $response->headers->set('Content-type', 'application/'.($is_xhr ? 'json' : 'x-javascript'));

        // Allow CORS requests only from dev machines
        $allowedIps = $this->coreParametersHelper->get('dev_hosts', []);
        if (in_array($request->getClientIp(), $allowedIps, true)) {
            $response->headers->set('Access-Control-Allow-Origin', '*');
        }

        return $response;
    }

    /**
     * This action will receive a POST when the session status changes.
     * A POST will also be made when a customer joins the session and when the session ends
     * (whether or not a customer joined).
     *
     * @return mixed[]|JsonResponse|RedirectResponse|Response
     *
     * @throws AccessDeniedHttpException
     * @throws \InvalidArgumentException
     * @throws BadRequestHttpException
     */
    public function sessionChangedAction(Request $request)
    {
        $myIntegration     = $this->integrationHelper->getIntegrationObject('Gototraining');

        if (!$myIntegration || !$myIntegration->getIntegrationSettings()->getIsPublished()) {
            return $this->accessDenied(false, 'ERROR: GoToTraining is not enabled');
        }

        $post = $request->request->all();

        try {
            $productId   = $post['sessionId'];
            $eventDesc   = sprintf('%s (%s)', $productId, $post['status']);
            $eventName   = $this->goToHelper->getCleanString(
                $eventDesc
            ).'_#'.$productId;
            $product = 'assist';
            $this->goToModel->syncEvent($product, (string) $productId, $eventName, $eventDesc);
        } catch (\Exception $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception, $exception->getCode());
        }

        return new Response('OK');
    }
}
