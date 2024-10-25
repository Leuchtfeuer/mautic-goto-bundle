<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Form\Validator;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class GotoApiBlacklistValidator extends ConstraintValidator
{
    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$value) {
            return;
        }

        $patterns = $this->coreParametersHelper->get('goto_api_blacklist_patterns', []);

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                /** @phpstan-ignore-next-line */
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ blacklisted }}', $pattern)
                    ->addViolation();

                break;
            }
        }
    }
}
