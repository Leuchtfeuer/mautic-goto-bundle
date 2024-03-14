<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Form\Type;

use MauticPlugin\LeuchtfeuerGoToBundle\Helper\GoToHelper;
use MauticPlugin\LeuchtfeuerGoToBundle\Helper\GoToProductTypes;
use MauticPlugin\LeuchtfeuerGoToBundle\Model\GoToModel;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class GoToCampaignEventType.
 */
class GoToCampaignEventType extends AbstractType
{
    /**
     * GoToCampaignEventType constructor.
     */
    public function __construct(
        protected GoToModel $model,
        protected TranslatorInterface $translator,
        protected GoToHelper $goToHelper
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @throws ServiceCircularReferenceException
     * @throws ServiceNotFoundException
     * @throws \InvalidArgumentException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!(array_key_exists('attr', $options) && array_key_exists('data-product', $options['attr']))
            || !GoToProductTypes::isValidValue($options['attr']['data-product'])
            || !$this->goToHelper->isAuthorized('Goto'.$options['attr']['data-product'])
        ) {
            return;
        }

        $product        = $options['attr']['data-product'];
        $eventNamesDesc = $this->model->getDistinctEventNamesDesc($options['attr']['data-product']);

        $choices = [
            'attendedToAtLeast' => $this->translator->trans('plugin.citrix.criteria.'.$product.'.attended'),
        ];

        if (GoToProductTypes::GOTOWEBINAR === $product || GoToProductTypes::GOTOTRAINING === $product) {
            $choices['registeredToAtLeast'] =
                $this->translator->trans('plugin.citrix.criteria.'.$product.'.registered');
        }

        $builder->add(
            'event-criteria-'.$product,
            ChoiceType::class,
            [
                'label'   => $this->translator->trans('plugin.citrix.decision.criteria'),
                'choices' => array_flip($choices),
            ]
        );

        $choices = array_replace(
            ['ANY' => $this->translator->trans('plugin.citrix.event.'.$product.'.any')],
            $eventNamesDesc
        );

        $builder->add(
            $product.'-list',
            ChoiceType::class,
            [
                'label'    => $this->translator->trans('plugin.citrix.decision.'.$product.'.list'),
                'choices'  => array_flip($choices),
                'multiple' => true,
            ]
        );
    }

    public function getName(): string
    {
        return 'citrix_campaign_event';
    }
}
