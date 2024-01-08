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
 * Class GoToCampaignActionType.
 */
class GoToCampaignActionType extends AbstractType
{
    /**
     * GoToCampaignEventType constructor.
     */
    public function __construct(
        protected GoToModel $model,
        protected TranslatorInterface $translator,
        private GoToHelper $goToHelper
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
        $c = null;
        if (!(array_key_exists('attr', $options) && array_key_exists('data-product', $options['attr']))
            || !GoToProductTypes::isValidValue($options['attr']['data-product'])
            || !$this->goToHelper->isAuthorized('Goto'.$options['attr']['data-product'])
        ) {
            return;
        }

        $product = $options['attr']['data-product'];

        $choices = [
            'webinar_register'     => $this->translator->trans('plugin.citrix.action.register.webinar'),
            'meeting_start'        => $this->translator->trans('plugin.citrix.action.start.meeting'),
            'training_register'    => $this->translator->trans('plugin.citrix.action.register.training'),
            'training_start'       => $this->translator->trans('plugin.citrix.action.start.training'),
            'assist_screensharing' => $this->translator->trans('plugin.citrix.action.screensharing.assist'),
        ];

        $newChoices = [];
        foreach ($choices as $k => $c) {
            if (str_starts_with($k, $product)) {
                $newChoices[$k] = $c;
            }
        }

        $builder->add(
            'event-criteria-'.$product,
            ChoiceType::class,
            [
                'label'   => $this->translator->trans('plugin.citrix.action.criteria'),
                'choices' => array_flip($newChoices),
            ]
        );

        $productArray= $this->model->getProducts($product, new \DateTime('now'), false, false, false);

        if (GoToProductTypes::GOTOASSIST !== $product) {
            $builder->add(
                $product.'-list',
                ChoiceType::class,
                [
                    'label'    => $this->translator->trans('plugin.citrix.decision.'.$product.'.list'),
                    'choices'  => array_flip($productArray),
                    'multiple' => true,
                ]
            );
        }

        if (array_key_exists('meeting_start', $newChoices)
            || array_key_exists('training_start', $newChoices)
            || array_key_exists('assist_screensharing', $newChoices)
        ) {
            $defaultOptions = [
                'label'      => 'plugin.citrix.emailtemplate',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'plugin.citrix.emailtemplate_descr',
                ],
                'required' => true,
                'multiple' => false,
            ];

            if (array_key_exists('list_options', $options)) {
                if (isset($options['list_options']['attr'])) {
                    $defaultOptions['attr'] = array_merge($defaultOptions['attr'], $options['list_options']['attr']);
                    unset($options['list_options']['attr']);
                }

                $defaultOptions = array_merge($defaultOptions, $options['list_options']);
            }

            $builder->add('template', 'email_list', $defaultOptions);
        }
    }

    public function getName(): string
    {
        return 'citrix_campaign_action';
    }
}
