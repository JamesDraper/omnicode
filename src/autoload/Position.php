<?php

declare(strict_types=1);

namespace OmniCode;

final class Position
{
    public function __construct(
        private readonly int $column,
        private readonly int $line,
    ) {}

    public function getColumn(): int
    {
        return $this->column;
    }

    public function getLine(): int
    {
        return $this->line;
    }
}
