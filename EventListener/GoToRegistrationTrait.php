<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\EventListener;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\LeuchtfeuerGoToBundle\Entity\GoToEventTypes;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait GoToRegistrationTrait
{
    /**
     * @param mixed[] $productsToRegister
     *
     * @throws BadRequestHttpException
     * @throws ServiceCircularReferenceException
     * @throws ServiceNotFoundException
     * @throws ORMInvalidArgumentException
     * @throws OptimisticLockException
     * @throws \InvalidArgumentException
     */
    public function registerProduct(string $product, Lead $currentLead, array $productsToRegister): void
    {
        $leadFields = $currentLead->getProfileFields();
        $email      = $leadFields['email'] ?? '';
        $firstname  = $leadFields['firstname'] ?? '';
        $lastname   = $leadFields['lastname'] ?? '';
        $company    = htmlspecialchars_decode($leadFields['company'] ?? '');

        if ('' !== $email && '' !== $firstname && '' !== $lastname) {
            foreach ($productsToRegister as $productToRegister) {
                $productId = $productToRegister['productId'];

                $isRegistered = $this->goToHelper->registerToProduct(
                    $product,
                    $productId,
                    $email,
                    $firstname,
                    $lastname,
                    $company
                );
                if ($isRegistered) {
                    $eventName = $this->goToHelper->getCleanString(
                        $productToRegister['productTitle']
                    ).'_#'.$productToRegister['productId'];

                    $this->goToModel->addEvent(
                        $product,
                        $email,
                        $eventName,
                        $productToRegister['productTitle'],
                        GoToEventTypes::REGISTERED,
                        $currentLead
                    );
                } else {
                    throw new BadRequestHttpException('Unable to register!');
                }
            }
        } else {
            throw new BadRequestHttpException('Mandatory lead fields not found!');
        }
    }
}
