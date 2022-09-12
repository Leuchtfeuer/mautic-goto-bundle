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

use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticGoToBundle\Entity\GoToEventTypes;
use MauticPlugin\MauticGoToBundle\Helper\GoToHelper;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait GoToRegistrationTrait
{
    /**
     * @param string $product
     * @param Lead   $currentLead
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \InvalidArgumentException
     */
    public function registerProduct($product, $currentLead, array $productsToRegister)
    {
        $leadFields                                   = $currentLead->getProfileFields();
        list($email, $firstname, $lastname, $company) = [
            array_key_exists('email', $leadFields) ? $leadFields['email'] : '',
            array_key_exists('firstname', $leadFields) ? $leadFields['firstname'] : '',
            array_key_exists('lastname', $leadFields) ? $leadFields['lastname'] : '',
            array_key_exists('company', $leadFields) ? $leadFields['company'] : '',
        ];

        if ('' !== $email && '' !== $firstname && '' !== $lastname) {
            foreach ($productsToRegister as $productToRegister) {
                $productId = $productToRegister['productId'];

                $isRegistered = GoToHelper::registerToProduct(
                    $product,
                    $productId,
                    $email,
                    $firstname,
                    $lastname,
                    $company
                );
                if ($isRegistered) {
                    $eventName = GoToHelper::getCleanString(
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
