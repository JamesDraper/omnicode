<?php

declare(strict_types=1);

namespace OmniCode\Token;

enum Index: int
{
    case WHITE_SPACE = 1;

    case NEW_LINE = 2;

    case KEYWORD_FUNC = 3;

    case KEYWORD_RETURN = 4;

    case KEYWORD_INT = 5;

    case IDENTIFIER = 6;

    case OPEN_PARENTHESIS = 7;

    case CLOSE_PARENTHESIS = 8;

    case COMMA = 9;

    case COLON = 10;

    case PLUS = 11;
}
