<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\EventListener;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\LeuchtfeuerGoToBundle\Entity\GoToEventTypes;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait GoToStartTrait
{
    protected EmailModel $emailModel;

    public function setEmailModel(EmailModel $emailModel): void
    {
        $this->emailModel = $emailModel;
    }

    /**
     * @throws BadRequestHttpException
     * @throws ServiceCircularReferenceException
     * @throws ServiceNotFoundException
     * @throws ORMInvalidArgumentException
     * @throws OptimisticLockException
     * @throws \InvalidArgumentException
     * @throws ORMException
     */
    public function startProduct(string $product, Lead $lead, array $productsToStart, mixed $emailId = null, mixed $actionId = null): void
    {
        $leadFields                         = $lead->getProfileFields();
        $email                              = $leadFields['email'] ?? '';
        $firstname                          = $leadFields['firstname'] ?? '';
        $lastname                           = $leadFields['lastname'] ?? '';

        if ('' !== $email && '' !== $firstname && '' !== $lastname) {
            foreach ($productsToStart as $productToStart) {
                $productId = $productToStart['productId'];

                $hostUrl = $this->goToHelper->startToProduct(
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
                        if ($this->goToHelper->isAuthorized('Goto'.$product)) {
                            $params = [
                                'product'     => $product,
                                'productLink' => $hostUrl,
                                'productText' => sprintf($this->translator->trans('plugin.citrix.start.producttext'), ucfirst($product)),
                            ];

                            $button = $this->templating->render(
                                'LeuchtfeuerGoToBundle:SubscribedEvents\EmailToken:token.html.php',
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
                    $eventName = $this->goToHelper->getCleanString(
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
