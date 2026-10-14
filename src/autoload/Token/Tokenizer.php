<?php

declare(strict_types=1);

namespace OmniCode\Token;

use OmniCode\Position;

use LogicException;

use function preg_match;
use function sprintf;
use function strlen;

class Tokenizer
{
    /**
     * Patterns in the order they are tried. The first one that matches wins, so keywords come
     * before identifiers.
     */
    private const array PATTERNS = [
        [Index::WHITE_SPACE, '[ \t]+'],
        [Index::NEW_LINE, '\r\n|\r|\n'],
        [Index::KEYWORD_FUNC, 'func\b'],
        [Index::KEYWORD_RETURN, 'return\b'],
        [Index::KEYWORD_INT, 'int\b'],
        [Index::IDENTIFIER, '[A-Za-z_][A-Za-z0-9_]*'],
        [Index::OPEN_PARENTHESIS, '\('],
        [Index::CLOSE_PARENTHESIS, '\)'],
        [Index::COMMA, ','],
        [Index::COLON, ':'],
        [Index::PLUS, '\+'],
    ];

    /**
     * @return list<Token>
     *
     * @throws SyntaxException
     * @throws LogicException
     */
    public function tokenize(string $source): array
    {
        $length = strlen($source);
        $tokens = [];
        $offset = 0;
        $column = 1;
        $line = 1;

        while ($offset < $length) {
            $position = new Position(column: $column, line: $line);
            $token = $this->findToken($source, $offset, $position);
            $tokens[] = $token;
            $offset += strlen($token->getText());

            if ($token->getIndex() === Index::NEW_LINE) {
                $column = 1;
                ++$line;
            } else {
                $column += strlen($token->getText());
            }
        }

        return $tokens;
    }

    /**
     * @throws SyntaxException
     * @throws LogicException
     */
    private function findToken(string $source, int $offset, Position $position): Token
    {
        foreach (self::PATTERNS as [$index, $pattern]) {
            $text = $this->match($pattern, $source, $offset);

            if ($text !== null) {
                return new Token($position, $index, $text);
            }
        }

        throw new SyntaxException($position, sprintf(
            'Unexpected character "%s" on line %d, column %d.',
            $this->getCharacterAt($source, $offset),
            $position->getLine(),
            $position->getColumn(),
        ));
    }

    /**
     * Returns the text matching the pattern exactly at the offset, or null when it does not
     * occur there.
     *
     * @return non-empty-string|null
     *
     * @throws LogicException
     */
    private function match(string $pattern, string $source, int $offset): ?string
    {
        // The A modifier anchors the match at the offset rather than searching ahead from it.
        $regex = sprintf('~%s~A', $pattern);
        $result = @preg_match($regex, $source, $matches, 0, $offset);

        if ($result === false) {
            throw new LogicException(sprintf(
                'Invalid regex: "%s".',
                $regex,
            ));
        }

        if ($result === 0) {
            return null;
        }

        // An empty match would never advance the tokenizer, so it is a broken pattern.
        if ($matches[0] === '') {
            throw new LogicException(sprintf(
                'The token pattern "%s" matched an empty string.',
                $pattern,
            ));
        }

        return $matches[0];
    }

    private function getCharacterAt(string $source, int $offset): string
    {
        // Report the whole UTF-8 character where possible. If the source is not valid UTF-8 the
        // match fails, and the single offending byte is reported instead.
        if (@preg_match('~.~suA', $source, $matches, 0, $offset) === 1) {
            return $matches[0];
        }

        return $source[$offset];
    }
}
