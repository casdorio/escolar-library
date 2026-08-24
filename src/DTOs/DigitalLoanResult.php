<?php

declare(strict_types=1);

namespace Escolar\Library\DTOs;

use Escolar\Library\Models\DigitalLoan;

final class DigitalLoanResult
{
    private function __construct(
        public readonly bool $allowed,
        public readonly ?string $reason = null,
        public readonly ?DigitalLoan $loan = null,
    ) {}

    public static function allow(DigitalLoan $loan): self
    {
        return new self(true, null, $loan);
    }

    public static function deny(string $reason): self
    {
        return new self(false, $reason);
    }
}
