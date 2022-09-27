<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticGoToBundle\EventListener;

use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticGoToBundle\Entity\GoToEventTypes;
use MauticPlugin\MauticGoToBundle\Helper\GoToHelper;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait GoToStartTrait
{
    /**
     * @var EmailModel
     */
    protected $emailModel;

    public function setEmailModel(EmailModel $emailModel)
    {
        $this->emailModel = $emailModel;
    }

    /**
     * @param string $product
     * @param Lead   $lead
     * @param        $emailId
     * @param        $actionId
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \InvalidArgumentException
     * @throws \Doctrine\ORM\ORMException
     */
    public function startProduct($product, $lead, array $productsToStart, $emailId = null, $actionId = null)
    {
        $leadFields                         = $lead->getProfileFields();
        $email                              = $leadFields['email'] ?? '';
        $firstname                          = $leadFields['firstname'] ?? '';
        $lastname                           = $leadFields['lastname'] ?? '';

        if ('' !== $email && '' !== $firstname && '' !== $lastname) {
            foreach ($productsToStart as $productToStart) {
                $productId = $productToStart['productId'];

                $hostUrl = GoToHelper::startToProduct(
                    $product,
                    $productId,
                    $email,
                    $firstname,
                    $lastname
                );

                if ('' !== $hostUrl) {
                    // send email using template from form action properties
                    // and replace the tokens in the body with the hostUrl

                    $emailEntity = $this->emailModel->getEntity($emailId);

                    // make sure the email still exists and is published
                    if (null !== $emailEntity && $emailEntity->isPublished()) {
                        $content = $emailEntity->getCustomHtml();
                        // replace tokens
                        if (GoToHelper::isAuthorized('Goto'.$product)) {
                            $params = [
                                'product'     => $product,
                                'productLink' => $hostUrl,
                                'productText' => sprintf($this->translator->trans('plugin.citrix.start.producttext'), ucfirst($product)),
                            ];

                            $button = $this->templating->render(
                                'MauticGoToBundle:SubscribedEvents\EmailToken:token.html.php',
                                $params
                            );
                            $content = str_replace('{'.$product.'_button}', $button, $content);
                        } else {
                            // remove the token
                            $content = str_replace('{'.$product.'_button}', '', $content);
                        }

                        // set up email data
                        $emailEntity->setCustomHtml($content);
                        $leadFields['id'] = $lead->getId();
                        $options          = ['source' => ['trigger', $actionId]];
                        $this->emailModel->sendEmail($emailEntity, $leadFields, $options);
                    } else {
                        throw new BadRequestHttpException('Unable to load emal template!');
                    }

                    // add event to DB
                    $eventName = GoToHelper::getCleanString(
                        $productToStart['productTitle']
                    ).'_#'.$productToStart['productId'];

                    $this->goToModel->addEvent(
                        $product,
                        $email,
                        $eventName,
                        $productToStart['productTitle'],
                        GoToEventTypes::STARTED,
                        $lead
                    );
                } else {
                    throw new BadRequestHttpException('Unable to start!');
                }
            }
        } else {
            throw new BadRequestHttpException('Mandatory lead fields not found!');
        }
    }
}
