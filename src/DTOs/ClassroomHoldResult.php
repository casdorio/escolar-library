<?php

declare(strict_types=1);

namespace Escolar\Library\DTOs;

use Escolar\Library\Models\ClassroomHold;

final class ClassroomHoldResult
{
    private function __construct(
        public readonly bool $allowed,
        public readonly ?string $reason = null,
        public readonly ?ClassroomHold $hold = null,
    ) {}

    public static function allow(ClassroomHold $hold, ?string $reason = null): self
    {
        return new self(true, $reason, $hold);
    }

    public static function deny(string $reason): self
    {
        return new self(false, $reason);
    }
}
