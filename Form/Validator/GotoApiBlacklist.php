<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Form\Validator;

use Symfony\Component\Validator\Constraint;

class GotoApiBlacklist extends Constraint
{
    public string $message = 'The value contains blacklisted characters or patterns: "{{ blacklisted }}"';
}
