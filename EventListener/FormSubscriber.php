<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\EventListener;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Exception\BadConfigurationException;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\SubmissionRepository;
use Mautic\FormBundle\Event as Events;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\Exception\ValidationException;
use Mautic\FormBundle\FormEvents;
use Mautic\FormBundle\Model\FormModel;
use Mautic\FormBundle\Model\SubmissionModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Event\PluginIntegrationRequestEvent;
use Mautic\PluginBundle\PluginEvents;
use MauticPlugin\LeuchtfeuerGoToBundle\Form\Type\GoToActionType;
use MauticPlugin\LeuchtfeuerGoToBundle\Form\Type\GoToListType;
use MauticPlugin\LeuchtfeuerGoToBundle\Form\Validator\GotoApiBlacklist;
use MauticPlugin\LeuchtfeuerGoToBundle\GoToEvents;
use MauticPlugin\LeuchtfeuerGoToBundle\Helper\GoToHelper;
use MauticPlugin\LeuchtfeuerGoToBundle\Helper\GoToProductTypes;
use MauticPlugin\LeuchtfeuerGoToBundle\Model\GoToModel;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Process\Exception\InvalidArgumentException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * Class FormSubscriber.
 */
class FormSubscriber implements EventSubscriberInterface
{
    use GoToRegistrationTrait;
    use GoToStartTrait;

    public function __construct(
        private GoToModel $goToModel,
        private FormModel $formModel,
        private SubmissionModel $submissionModel,
        private TranslatorInterface $translator,
        private EntityManager $entityManager,
        private GoToHelper $goToHelper,
        private Environment $twig,
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::FORM_ON_BUILD                    => ['onFormBuilder', 0],
            FormEvents::FORM_ON_SUBMIT                   => ['onFormSubmit', 0],
            GoToEvents::ON_GOTO_REGISTER_ACTION          => ['onWebinarRegister', 0],
            GoToEvents::ON_MEETING_START_ACTION          => ['onMeetingStart', 0],
            GoToEvents::ON_TRAINING_REGISTER_ACTION      => ['onTrainingRegister', 0],
            GoToEvents::ON_TRAINING_START_ACTION         => ['onTrainingStart', 0],
            GoToEvents::ON_ASSIST_REMOTE_ACTION          => ['onAssistRemote', 0],
            GoToEvents::ON_FORM_VALIDATE_ACTION          => ['onFormValidate', 0],
            GoToEvents::ON_FORM_VALIDATE                 => ['onFormFieldValidate', 0],
            FormEvents::FORM_PRE_SAVE                    => ['onFormPreSave', 0],
            PluginEvents::PLUGIN_ON_INTEGRATION_REQUEST  => ['onRequest', 0],
            PluginEvents::PLUGIN_ON_INTEGRATION_RESPONSE => ['onResponse', 0],
        ];
    }

    /**
     * @throws ValidationException
     */
    private function _doRegistration(SubmissionEvent $event, string $product, ?string $startType = null): void
    {
        $submission = $event->getSubmission();
        $form       = $submission->getForm();
        $post       = $event->getPost();
        $fields     = $form->getFields();
        $actions    = $form->getActions();

        try {
            // gotoassist screen sharing does not need a product
            if ('assist' !== $product) {
                // check if there are products in the actions
                /** @var Action $action */
                foreach ($actions as $action) {
                    if (str_starts_with($action->getType(), 'plugin.citrix.action')) {
                        $actionAction = preg_filter('/^.+\.([^\.]+\.[^\.]+)$/', '$1', $action->getType());
                        $actionAction = str_replace('.', '_', $actionAction);

                        if (!array_key_exists($actionAction, $submission->getResults())) {
                            // add new hidden field to store the product id
                            $field = new Field();
                            $field->setType('hidden');
                            $field->setLabel(ucfirst($product).' ID');
                            $field->setAlias($actionAction);
                            $field->setForm($form);
                            $field->setOrder(99999);
                            $field->setSaveResult(true);
                            $form->addField($actionAction, $field);
                            $this->entityManager->persist($form);
                            /* @var FormModel $formModel */
                            $this->formModel->createTableSchema($form);
                        }
                    }
                }
            }

            $productsToRegister = $this->getProductsFromPost($actions, $fields, $post, $product);

            if ('assist' === $product || ([] !== $productsToRegister)) {
                $results = $submission->getResults();

                // persist the new values
                if ('assist' !== $product) {
                    // replace the submitted value with something more legible
                    foreach ($productsToRegister as $productToRegister) {
                        if (null !== $productToRegister['productId']) {
                            $results[$productToRegister['fieldName']] = $productToRegister['productTitle'].' ('.$productToRegister['productId'].')';
                        }
                    }

                    /** @var SubmissionRepository $repo */
                    $repo             = $this->submissionModel->getRepository();
                    $resultsTableName = $repo->getResultsTableName($form->getId(), $form->getAlias());
                    $tableKeys        = ['submission_id' => $submission->getId()];
                    $this->entityManager
                        ->getConnection()
                        ->update($resultsTableName, $results, $tableKeys);
                } else {
                    // dummy field for assist
                    $productsToRegister[] = // needed because there are no ids
                        [
                            'fieldName'    => $startType,
                            'productId'    => $startType,
                            'productTitle' => $startType,
                        ];
                }

                $currentLead = $event->getLead();

                // execute action
                if ($currentLead instanceof Lead) {
                    if (null !== $startType) {
                        /** @var Action $action */
                        foreach ($actions as $action) {
                            $actionAction = preg_filter('/^.+\.([^\.]+\.[^\.]+)$/', '$1', $action->getType());
                            if ($actionAction === $startType) {
                                if (array_key_exists('template', $action->getProperties())) {
                                    $emailId = $action->getProperties()['template'];
                                    $this->startProduct(
                                        $product,
                                        $currentLead,
                                        $productsToRegister,
                                        $emailId,
                                        $action->getId()
                                    );
                                } else {
                                    throw new BadRequestHttpException('Email template not found!');
                                }
                            }
                        }
                    } else {
                        $this->registerProduct($product, $currentLead, $productsToRegister);
                    }
                } else {
                    throw new BadRequestHttpException('Lead not found!');
                }
            } else {
                throw new BadRequestHttpException('There are no products to '.((null === $startType) ? 'register' : 'start'));
            }
            // end-block
        } catch (\Exception $exception) {
            $this->goToHelper->log('onProductRegistration - '.$product.': '.$exception->getMessage());
            $validationException = new ValidationException($exception->getMessage());
            $validationException->setViolations(
                [
                    'email' => $exception->getMessage(),
                ]
            );
            throw $validationException;
        }
    }

    public function onWebinarRegister(SubmissionEvent $event): void
    {
        $this->_doRegistration($event, GoToProductTypes::GOTOWEBINAR);
    }

    public function onMeetingStart(SubmissionEvent $event): void
    {
        $this->_doRegistration($event, GoToProductTypes::GOTOMEETING, 'start.meeting');
    }

    public function onTrainingRegister(SubmissionEvent $event): void
    {
        $this->_doRegistration($event, GoToProductTypes::GOTOTRAINING);
    }

    public function onTrainingStart(SubmissionEvent $event): void
    {
        $this->_doRegistration($event, GoToProductTypes::GOTOTRAINING, 'start.training');
    }

    public function onAssistRemote(SubmissionEvent $event): void
    {
        $this->_doRegistration($event, GoToProductTypes::GOTOASSIST, 'screensharing.assist');
    }

    /**
     * Helper function to debug REST responses.
     */
    public function onResponse(PluginIntegrationRequestEvent $event): void
    {
        //        /** @var Response $response */
        //        $response = $event->getResponse();
        //        $this->goToHelper->log(
        //            PHP_EOL. //$response->getStatusCode() . ' ' .
        //            print_r($response, true)
        //        );
    }

    /**
     * Helper function to debug REST requests.
     */
    public function onRequest(PluginIntegrationRequestEvent $event): void
    {
        // clean parameter that was breaking the call
        if (preg_match('#\/G2W\/rest\/#', $event->getUrl())) {
            $params = $event->getParameters();
            unset($params['access_token']);
            $event->setParameters($params);
        }
    }

    /**
     * @throws ServiceCircularReferenceException
     * @throws ServiceNotFoundException
     */
    public function onFormValidate(Events\ValidationEvent $event): void
    {
        $field        = $event->getField();
        $eventType    = preg_filter('/^plugin\.citrix\.select\.(.*)$/', '$1', $field->getType());
        $doValidation = $this->goToHelper->isAuthorized('Goto'.$eventType);

        if ($doValidation) {
            $list   = $this->goToModel->getProducts($eventType, new \DateTime('now'), null, false, false);
            $values = $event->getValue();

            if (!is_array($values) && !is_object($values)) {
                $values = [$values];
            }

            /** @phpstan-ignore-next-line */
            foreach ($values as $value) {
                if (!array_key_exists($value, $list) && !empty($value)) {
                    $event->failedValidation(
                        $value.': '.$this->translator->trans('plugin.citrix.'.$eventType.'.nolongeravailable')
                    );
                }
            }
        }
    }

    /**
     * @param mixed[] $post
     *
     * @return mixed[]
     */
    private function getProductsFromPost(Collection $actions, Collection $fields, array $post, string $product): array
    {
        $productList = [];

        $products = [];

        /** @var Field $field */
        foreach ($fields as $field) {
            if ('plugin.citrix.select.'.$product === $field->getType()) {
                if (0 === (!is_countable($productList) ? 0 : count($productList))) {
                    $productList = $this->goToModel->getProducts($product);
                }

                $alias      = $field->getAlias();
                $productIds = $post[$alias];

                /** @phpstan-ignore-next-line */
                if (!is_array($productIds) && !is_object($productIds)) {
                    $productIds = [$productIds];
                }

                /** @phpstan-ignore-next-line */
                foreach ($productIds as $productId) {
                    if (null === $productId) { // We do have to ignore optional fields
                        continue;
                    }
                    $products[] = [
                        'fieldName'    => $alias,
                        'productId'    => $productId,
                        'productTitle' => array_key_exists(
                            $productId,
                            $productList
                        ) ? $productList[$productId] : 'untitled',
                    ];
                }
            }
        }

        // gotoassist screen sharing does not need a product
        if ('assist' !== $product) {
            // check if there are products in the actions
            /** @var Action $action */
            foreach ($actions as $action) {
                if (str_starts_with($action->getType(), 'plugin.citrix.action')) {
                    if (0 === (!is_countable($productList) ? 0 : count($productList))) {
                        $productList = $this->goToModel->getProducts($product);
                    }

                    $actionProduct = preg_filter('/^.+\.([^\.]+)$/', '$1', $action->getType());
                    if (!$this->goToHelper->isAuthorized('Goto'.$actionProduct)) {
                        continue;
                    }

                    $actionAction = preg_filter('/^.+\.([^\.]+\.[^\.]+)$/', '$1', $action->getType());
                    $productId    = $action->getProperties()['product'];
                    if (array_key_exists(
                        $productId,
                        $productList
                    )) {
                        $products[] = [
                            'fieldName'    => str_replace('.', '_', $actionAction),
                            'productId'    => $productId,
                            'productTitle' => $productList[$productId],
                        ];
                    }
                }
            }
        }

        return $products;
    }

    /**
     * @throws ValidationException
     */
    public function onFormPreSave(Events\FormEvent $event): void
    {
        $form   = $event->getForm();
        $fields = $form->getFields()->getValues();

        // Verify if the form is well configured
        if (0 !== (!is_countable($fields) ? 0 : count($fields))) {
            $violations = $this->_checkFormValidity($form);
            if ([] !== $violations) {
                $event->stopPropagation();
                $error     = implode(' * ', $violations);
                throw (new ValidationException($error))->setViolations($violations);
            }
        }
    }

    public function onFormSubmit(SubmissionEvent $event): void
    {
    }

    /**
     * @return mixed[]
     *
     * @throws \InvalidArgumentException
     */
    private function _checkFormValidity(Form $form): array
    {
        $errors  = [];
        $actions = $form->getActions();
        $fields  = $form->getFields();

        if ($actions->count() && $fields->count()) {
            $actionFields = [
                'register.webinar'     => ['email', 'firstname', 'lastname'],
                'register.training'    => ['email', 'firstname', 'lastname'],
                'start.meeting'        => ['email'],
                'start.training'       => ['email'],
                'screensharing.assist' => ['email', 'firstname', 'lastname'],
            ];

            $errorMessages = [
                'lead_field_not_found' => $this->translator->trans(
                    'plugin.citrix.formaction.validator.leadfieldnotfound'
                ),
                'field_not_found'          => $this->translator->trans('plugin.citrix.formaction.validator.fieldnotfound'),
                'field_should_be_required' => $this->translator->trans(
                    'plugin.citrix.formaction.validator.fieldshouldberequired'
                ),
            ];

            /** @var Action $action */
            foreach ($actions as $action) {
                if (str_starts_with($action->getType(), 'plugin.citrix.action')) {
                    $actionProduct = preg_filter('/^.+\.([^\.]+)$/', '$1', $action->getType());
                    if (!$this->goToHelper->isAuthorized('Goto'.$actionProduct)) {
                        continue;
                    }

                    $actionAction = preg_filter('/^.+\.([^\.]+\.[^\.]+)$/', '$1', $action->getType());

                    // get lead fields
                    $currentLeadFields = [];
                    foreach ($fields as $field) {
                        $leadField = $field->getLeadField(); // @phpstan-ignore-line
                        if (null !== $leadField && '' !== $leadField) {
                            $currentLeadFields[$leadField] = $field->getIsRequired();
                        }
                    }

                    $props = $action->getProperties();
                    if (array_key_exists('product', $props) && 'form' === $props['product']) {
                        // the product will be selected from a list in the form
                        // search for the select field and perform validation for a corresponding action

                        $hasCitrixListField = false;

                        /** @var Field $field */
                        foreach ($fields as $field) {
                            $fieldProduct = preg_filter('/^.+\.([^\.]+)$/', '$1', $field->getType());

                            if ($fieldProduct === $actionProduct) {
                                $hasCitrixListField = true;
                            } elseif (in_array($field->getLeadField(), $actionFields[$actionAction]) && !$field->getIsRequired()) { // Mandatory fields @phpstan-ignore-line
                                /** @phpstan-ignore-next-line */
                                $errors[$field->getLeadField().'required'] = sprintf(
                                    $errorMessages['field_should_be_required'],
                                    $this->translator->trans('plugin.citrix.'.$field->getLeadField().'.listfield') // @phpstan-ignore-line
                                );
                            }
                        }

                        if (!$hasCitrixListField) {
                            $errors[$actionProduct.'listfield'] = sprintf(
                                $errorMessages['field_not_found'],
                                $this->translator->trans('plugin.citrix.'.$actionProduct.'.listfield')
                            );
                        }
                    }

                    // check for lead fields
                    $mandatoryFields = $actionFields[$actionAction];
                    foreach ($mandatoryFields as $mandatoryField) {
                        if (!array_key_exists($mandatoryField, $currentLeadFields)) {
                            $errors[$mandatoryField.'notfound'] = sprintf($errorMessages['lead_field_not_found'], $mandatoryField);
                        }
                    }
                }
                // end-if there is a Citrix action
            }
            // foreach $actions
        }

        return $errors;
    }

    /**
     * @throws InvalidArgumentException|BadConfigurationException
     */
    public function onFormBuilder(Events\FormBuilderEvent $event): void
    {
        $activeProducts = [];
        foreach (GoToProductTypes::toArray() as $p) {
            if ($this->goToHelper->isAuthorized('Goto'.$p)) {
                $activeProducts[] = $p;
            }
        }

        if ([] === $activeProducts) {
            return;
        }

        foreach ($activeProducts as $product) {
            // Select field
            $field = [
                'label'           => 'plugin.citrix.'.$product.'.listfield',
                'formType'        => GoToListType::class,
                'template'        => '@LeuchtfeuerGoTo/Field/citrixlist.html.twig',
                'listType'        => $product,
                'product_choices' => $this->goToModel->getProducts($product, null, null, false, true),
            ];
            $event->addFormField('plugin.citrix.select.'.$product, $field);

            $validator = [
                'eventName' => GoToEvents::ON_FORM_VALIDATE_ACTION,
                'fieldType' => 'plugin.citrix.select.'.$product,
            ];
            $event->addValidator('plugin.citrix.validate.'.$product, $validator);

            $event->addValidator('plugin.citrix.validate.goto.form', [
                'eventName' => GoToEvents::ON_FORM_VALIDATE,
            ]);

            // actions
            if (GoToProductTypes::GOTOWEBINAR === $product) {
                $action = [
                    'group'           => 'plugin.citrix.form.header',
                    'description'     => 'plugin.citrix.form.header.webinar',
                    'label'           => 'plugin.citrix.action.register.webinar',
                    'formType'        => GoToActionType::class,
                    'formTypeOptions' => [
                        'attr' => [
                            'data-product'        => $product,
                            'data-product-action' => 'register',
                        ],
                    ],
                    'template'  => '@MauticForm/Action/_generic.html.twig',
                    'eventName' => GoToEvents::ON_GOTO_REGISTER_ACTION,
                ];
                $event->addSubmitAction('plugin.citrix.action.register.webinar', $action);
            } elseif (GoToProductTypes::GOTOMEETING === $product) {
                $action = [
                    'group'           => 'plugin.citrix.form.header',
                    'description'     => 'plugin.citrix.form.header.meeting',
                    'label'           => 'plugin.citrix.action.start.meeting',
                    'formType'        => GoToActionType::class,
                    'template'        => '@MauticForm/Action/_generic.html.twig',
                    'eventName'       => GoToEvents::ON_MEETING_START_ACTION,
                    'formTypeOptions' => [
                        'attr' => [
                            'data-product'        => $product,
                            'data-product-action' => 'start',
                        ],
                    ],
                ];
                $event->addSubmitAction('plugin.citrix.action.start.meeting', $action);
            } elseif (GoToProductTypes::GOTOTRAINING === $product) {
                $action = [
                    'group'           => 'plugin.citrix.form.header',
                    'description'     => 'plugin.citrix.form.header.training',
                    'label'           => 'plugin.citrix.action.register.training',
                    'formType'        => GoToActionType::class,
                    'template'        => '@MauticForm/Action/_generic.html.twig',
                    'eventName'       => GoToEvents::ON_TRAINING_REGISTER_ACTION,
                    'formTypeOptions' => [
                        'attr' => [
                            'data-product'        => $product,
                            'data-product-action' => 'register',
                        ],
                    ],
                ];
                $event->addSubmitAction('plugin.citrix.action.register.training', $action);
                $action = [
                    'group'           => 'plugin.citrix.form.header',
                    'description'     => 'plugin.citrix.form.header.start.training',
                    'label'           => 'plugin.citrix.action.start.training',
                    'formType'        => GoToActionType::class,
                    'template'        => '@MauticForm/Action/_generic.html.twig',
                    'eventName'       => GoToEvents::ON_TRAINING_START_ACTION,
                    'formTypeOptions' => [
                        'attr' => [
                            'data-product'        => $product,
                            'data-product-action' => 'start',
                        ],
                    ],
                ];
                $event->addSubmitAction('plugin.citrix.action.start.training', $action);
            } elseif (GoToProductTypes::GOTOASSIST === $product) {
                $action = [
                    'group'           => 'plugin.citrix.form.header',
                    'description'     => 'plugin.citrix.form.header.assist',
                    'label'           => 'plugin.citrix.action.screensharing.assist',
                    'formType'        => GoToActionType::class,
                    'template'        => '@MauticForm/Action/_generic.html.twig',
                    'eventName'       => GoToEvents::ON_ASSIST_REMOTE_ACTION,
                    'formTypeOptions' => [
                        'attr' => [
                            'data-product'        => $product,
                            'data-product-action' => 'screensharing',
                        ],
                    ],
                ];
                $event->addSubmitAction('plugin.citrix.action.screensharing.assist', $action);
            }
        }
    }

    public function onFormFieldValidate(Events\ValidationEvent $event): void
    {
        $doValidation = $this->goToHelper->isAuthorized('Goto'.GoToProductTypes::GOTOWEBINAR);
        if (!$doValidation) {
            return;
        }

        $value = $event->getValue();
        if (!empty($value)) {
            $field  = $event->getField();
            $fields = [
                'firstname',
                'lastname',
                'first_name',
                'last_name',
                'company',
            ];
            if (in_array($field->getAlias(), $fields) && 'text' === $field->getType()) {
                $violations = $this->validator->validate($value, new GotoApiBlacklist());
                if (count($violations)) {
                    $errors = '';
                    /** @var ConstraintViolation $v */
                    foreach ($violations as $v) {
                        $transParameters            = $v->getParameters();
                        $transParameters['%label%'] = '['.$field->getLabel().']  ';

                        $errors .= $this->translator->trans('%label%'.$v->getMessage(), $transParameters, 'validators');
                    }

                    $event->failedValidation($errors);
                }
            }
        }
    }
}
