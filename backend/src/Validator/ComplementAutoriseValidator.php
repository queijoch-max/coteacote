<?php

namespace App\Validator;

use App\Service\ComplementFilter;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ComplementAutoriseValidator extends ConstraintValidator
{
    public function __construct(private readonly ComplementFilter $filter)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ComplementAutorise) {
            throw new UnexpectedTypeException($constraint, ComplementAutorise::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_string($value) || !$this->filter->estAutorise($value)) {
            // Message générique : on ne dit pas quel mot a été bloqué.
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
