<?php

declare(strict_types=1);

namespace OmniCode\Token;

use OmniCode\Position;

final class Token
{
    private readonly Position $position;

    private readonly Index $index;

    private readonly string $text;

    public function __construct(Position $position, Index $index, string $text)
    {
        $this->position = $position;
        $this->index = $index;
        $this->text = $text;
    }

    public function getPosition(): Position
    {
        return $this->position;
    }

    public function getIndex(): Index
    {
        return $this->index;
    }

    public function getText(): string
    {
        return $this->text;
    }
}
