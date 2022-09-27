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

use Mautic\FormBundle\Model\FieldModel;
use MauticPlugin\MauticGoToBundle\Helper\GoToHelper;
use MauticPlugin\MauticGoToBundle\Helper\GoToProductTypes;
use MauticPlugin\MauticGoToBundle\Model\GoToModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class FormFieldSelectType.
 */
class GoToActionType extends AbstractType
{
    /**
     * @var FieldModel
     */
    protected $model;

    /**
     * @var GoToModel
     */
    protected $goToModel;

    /**
     * GoToActionType constructor.
     */
    public function __construct(FieldModel $fieldModel, GoToModel $goToModel)
    {
        $this->model     = $fieldModel;
        $this->goToModel = $goToModel;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\Validator\Exception\ConstraintDefinitionException
     * @throws \Symfony\Component\Validator\Exception\InvalidOptionsException
     * @throws \Symfony\Component\Validator\Exception\MissingOptionsException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!(array_key_exists('attr', $options) && array_key_exists('data-product', $options['attr'])) ||
            !GoToProductTypes::isValidValue($options['attr']['data-product']) ||
            !GoToHelper::isAuthorized('Goto'.$options['attr']['data-product'])
        ) {
            return;
        }

        $product = $options['attr']['data-product'];

        $fields  = $this->model->getSessionFields($options['attr']['data-formid']);
        $choices = [
            '' => '',
        ];

        foreach ($fields as $f) {
            if (in_array(
                $f['type'],
                array_merge(
                    ['button', 'freetext', 'captcha'],
                    array_map(
                        static fn ($p) => 'plugin.citrix.select.'.$p,
                        GoToProductTypes::toArray()
                    )
                ),
                true
            )) {
                continue;
            }

            $choices[$f['id']] = $f['label'];
        }

        if (array_key_exists('data-product-action', $options['attr']) &&
            ('register' === $options['attr']['data-product-action'] ||
                'start' === $options['attr']['data-product-action'])
        ) {
            $products = [
                'form' => 'User selection from form',
            ];
            $products = array_replace($products, $this->goToModel->getProducts($product, new \DateTime('now'), false, false, false));

            $builder->add(
                'product',
                ChoiceType::class,
                [
                    'choices'    => array_flip($products),
                    'expanded'   => false,
                    'label_attr' => ['class' => 'control-label'],
                    'multiple'   => false,
                    'label'      => 'plugin.citrix.'.$product.'.listfield',
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => 'plugin.citrix.selectproduct.tooltip',
                    ],
                    'required'    => true,
                    'constraints' => [
                        new NotBlank(
                            ['message' => 'mautic.core.value.required']
                        ),
                    ],
                ]
            );
        }

        if (array_key_exists('data-product-action', $options['attr']) &&
            ('register' === $options['attr']['data-product-action'] ||
                'screensharing' === $options['attr']['data-product-action'])
        ) {
            $builder->add(
                'firstname',
                ChoiceType::class,
                [
                    'choices'    => array_flip($choices),
                    'expanded'   => false,
                    'label_attr' => ['class' => 'control-label'],
                    'multiple'   => false,
                    'label'      => 'plugin.citrix.first_name',
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => 'plugin.citrix.first_name.tooltip',
                    ],
                    'required'    => true,
                    'constraints' => [
                        new NotBlank(
                            ['message' => 'mautic.core.value.required']
                        ),
                    ],
                ]
            );

            $builder->add(
                'lastname',
                ChoiceType::class,
                [
                    'choices'    => array_flip($choices),
                    'expanded'   => false,
                    'label_attr' => ['class' => 'control-label'],
                    'multiple'   => false,
                    'label'      => 'plugin.citrix.last_name',
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => 'plugin.citrix.last_name.tooltip',
                    ],
                    'required'    => true,
                    'constraints' => [
                        new NotBlank(
                            ['message' => 'mautic.core.value.required']
                        ),
                    ],
                ]
            );
        }

        $builder->add(
            'email',
            ChoiceType::class,
            [
                'choices'    => array_flip($choices),
                'expanded'   => false,
                'label_attr' => ['class' => 'control-label'],
                'multiple'   => false,
                'label'      => 'plugin.citrix.selectidentifier',
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'plugin.citrix.selectidentifier.tooltip',
                ],
                'required'    => true,
                'constraints' => [
                    new NotBlank(
                        ['message' => 'mautic.core.value.required']
                    ),
                ],
            ]
        );

        $builder->add(
            'company',
            ChoiceType::class,
            [
                'choices'    => array_flip($choices),
                'expanded'   => false,
                'label_attr' => ['class' => 'control-label'],
                'multiple'   => false,
                'label'      => 'plugin.citrix.company',
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'plugin.citrix.company.tooltip',
                ],
                'required'    => false,
            ]
        );

        if (array_key_exists('data-product-action', $options['attr']) &&
            ('start' === $options['attr']['data-product-action'] ||
             'screensharing' === $options['attr']['data-product-action'])
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

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'citrix_submit_action';
    }
}
