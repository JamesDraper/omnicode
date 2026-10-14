<?php

declare(strict_types=1);

namespace OmniCode\Token;

use OmniCode\Position;

use Exception;

final class SyntaxException extends Exception
{
    private readonly Position $position;

    public function __construct(Position $position, string $message)
    {
        $this->position = $position;

        parent::__construct($message);
    }

    public function getPosition(): Position
    {
        return $this->position;
    }
}
