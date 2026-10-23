<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\Page;
use App\Repository\PageRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class UniquePageKindValidator extends ConstraintValidator
{
    public function __construct(private readonly PageRepository $pages)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniquePageKind) {
            throw new UnexpectedTypeException($constraint, UniquePageKind::class);
        }

        if (!$value instanceof Page) {
            throw new UnexpectedValueException($value, Page::class);
        }

        if (!$value->getKind()->isUnique()) {
            return;
        }

        $existing = $this->pages->findOneByKind($value->getKind());
        if (null !== $existing && $existing !== $value) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ kind }}', $value->getKind()->label())
                ->setParameter('{{ title }}', $existing->getTitle())
                ->atPath('kind')
                ->addViolation();
        }
    }
}
