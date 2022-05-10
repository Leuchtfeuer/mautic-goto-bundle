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

use DateTime;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use MauticPlugin\MauticGoToBundle\Helper\GoToDetailKeywords;
use MauticPlugin\MauticGoToBundle\Model\GoToModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class FormFieldSelectType.
 */
class GoToListType extends AbstractType
{
    private $citrixModel;

    public function __construct(GoToModel $citrixModel)
    {
        $this->citrixModel = $citrixModel;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $selectMessage = 'Please Select...';
        if (!empty($options['data'])) {
            $selectMessage = empty($options['data']['empty_value']) ? $selectMessage : $options['data']['empty_value'];
        }
        $builder->add(
            'empty_value',
            TextType::class,
            [
                'label'      => 'mautic.form.field.form.emptyvalue',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'data'       => $selectMessage,
                'required'   => false,
            ]
        );

        $products        = $this->citrixModel->getProducts('webinar', new \DateTime('now'), null, true, true);
        $active_products = [];
        foreach ($products as $key => $product) {
            $date = DateTime::createFromFormat('Y-m-d H:i:s.u', $product['date']['date']);
            if (false !== $date && STATUS_ACTIVE === $product['status']) {
                $active_products[$key] = $date->format('d.m.Y H:i').' '.(null !== $product['recurrence_key'] ? '(...) ' : '').$product['name'];
            }
        }

        $builder->add(
            'product_select',
            ChoiceType::class,
            [
                'choices' => array_flip($active_products),
                'multiple' => true,
                'label' => 'mautic.citrix.form.product.select',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'required' => true,
            ]
        );

        $data_above = [];
        if (!empty($options['data']['above_dropdown_details'])) {
            $data_above = $options['data']['above_dropdown_details'];
        }
        $builder->add(
            'above_dropdown_details',
            ChoiceType::class,
            [
                'choices'                   => GoToDetailKeywords::getKeyPairs(),
                'choice_translation_domain' => true,
                'expanded'                  => false,
                'multiple'                  => true,
                'label'                     => 'mautic.citrix.form.dropdown.above',
                'label_attr'                => ['class' => 'control-label'],
                'attr'                      => [
                    'class' => 'form-control',
                ],
                'required' => true,
                'data'     => $data_above,
            ]
        );

        $data_in = [GoToDetailKeywords::GOTODATE];
        if (!empty($options['data']['in_dropdown_details'])) {
            $data_in = $options['data']['in_dropdown_details'];
        }

        $builder->add(
            'in_dropdown_details',
            ChoiceType::class,
            [
                'choices'    => GoToDetailKeywords::getKeyPairs(),
                'data'       => $data_in,
                'multiple'   => true,
                'label'      => 'mautic.citrix.form.dropdown.within',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'required' => true,
            ]
        );

        $default = false;

        if (!empty($options['data'])) {
            $default = empty($options['data']['multiple']) ? false : true;
        }

        $builder->add(
            'multiple',
            YesNoButtonGroupType::class,
            [
                'label'    => 'mautic.citrix.form.multiple',
                'data'     => $default,
                'required' => true,
            ]
        );

        $default_separate = false;

        if (!empty($options['data'])) {
            $default_separate = empty($options['data']['separate']) ? false : true;
        }

        $builder->add(
            'separate',
            YesNoButtonGroupType::class,
            [
                'label'    => 'mautic.citrix.form.separate',
                'required' => true,
                'data'     => $default_separate,
            ]
        );

        $builder->add(
            'attribute_container',
            TextType::class,
            [
                'label'      => 'mautic.form.field.form.attribute.container',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
            ]
        );
        $builder->add(
            'attribute_title',
            TextType::class,
            [
                'label'      => 'mautic.form.field.form.attribute.title',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
            ]
        );

        $builder->add(
            'attribute_language',
            TextType::class,
            [
                'label'      => 'mautic.form.field.form.attribute.language',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
            ]
        );
        $builder->add(
            'attribute_author',
            TextType::class,
            [
                'label'      => 'mautic.form.field.form.attribute.author',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
            ]
        );
        $builder->add(
            'attribute_duration',
            TextType::class,
            [
                'label'      => 'mautic.form.field.form.attribute.duration',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
            ]
        );
        $builder->add(
            'attribute_date',
            TextType::class,
            [
                'label'      => 'mautic.form.field.form.attribute.date',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
            ]
        );
        $builder->add(
            'attribute_description',
            TextType::class,
            [
                'label'      => 'mautic.form.field.form.attribute.description',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'parentData' => [],
                'choices'    => null,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'citrix_list';
    }
}
