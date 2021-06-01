<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticGoToBundle\Form\Type;

use MauticPlugin\MauticGoToBundle\Helper\GoToHelper;
use MauticPlugin\MauticGoToBundle\Helper\GoToProductTypes;
use MauticPlugin\MauticGoToBundle\Model\GoToModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class GoToCampaignEventType.
 */
class GoToCampaignEventType extends AbstractType
{
    /**
     * @var GoToModel
     */
    protected $model;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * GoToCampaignEventType constructor.
     *
     * @param GoToModel         $model
     * @param TranslatorInterface $translator
     */
    public function __construct(GoToModel $model, TranslatorInterface $translator)
    {
        $this->model      = $model;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \InvalidArgumentException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!(array_key_exists('attr', $options) && array_key_exists('data-product', $options['attr']))
            || !GoToProductTypes::isValidValue($options['attr']['data-product'])
            || !GoToHelper::isAuthorized('Goto'.$options['attr']['data-product'])
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

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'citrix_campaign_event';
    }
}
