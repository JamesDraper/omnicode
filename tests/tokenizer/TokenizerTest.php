<?php

declare(strict_types=1);

namespace Tests\Tokenizer;

use OmniCode\Token\SyntaxException;
use OmniCode\Token\Tokenizer;
use OmniCode\Token\Index;
use OmniCode\Token\Token;

use Tests\TestCase;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

use LogicException;

use function file_get_contents;
use function array_map;
use function sprintf;

/**
 * @phpstan-type TokenShape array{
 *     line: int,
 *     column: int,
 *     index: Index,
 *     text: string,
 * }
 * @phpstan-type TokenizeScenario array{
 *     file: string,
 *     expected: list<TokenShape>,
 * }
 * @phpstan-type SyntaxErrorScenario array{
 *     file: string,
 *     line: int,
 *     column: int,
 *     message: string,
 * }
 */
final class TokenizerTest extends TestCase
{
    private const string SOURCE_FILES_DIRECTORY = __DIR__ . '/source_files';

    /**
     * @param list<TokenShape> $expected
     *
     * @throws SyntaxException
     * @throws LogicException
     */
    #[Test]
    #[DataProvider('tokenize_returns_every_token_in_the_source_provider')]
    public function tokenize_returns_every_token_in_the_source(string $file, array $expected): void
    {
        $tokenizer = new Tokenizer();
        $source = $this->readSourceFile($file);

        $result = $tokenizer->tokenize($source);

        $this->assertSame($expected, $this->toTokenShapes($result));
    }

    /**
     * @phpstan-return list<TokenizeScenario>
     */
    public static function tokenize_returns_every_token_in_the_source_provider(): array
    {
        return [
            // An empty file has no tokens.
            ['file' => 'empty.oc', 'expected' => []],

            // A complete function, including a blank first line and indentation.
            [
                'file' => 'add_function.oc',
                'expected' => [
                    ['line' => 1, 'column' => 1, 'index' => Index::NEW_LINE, 'text' => "\n"],
                    ['line' => 2, 'column' => 1, 'index' => Index::KEYWORD_FUNC, 'text' => 'func'],
                    ['line' => 2, 'column' => 5, 'index' => Index::WHITE_SPACE, 'text' => ' '],
                    ['line' => 2, 'column' => 6, 'index' => Index::IDENTIFIER, 'text' => 'add'],
                    ['line' => 2, 'column' => 9, 'index' => Index::OPEN_PARENTHESIS, 'text' => '('],
                    ['line' => 2, 'column' => 10, 'index' => Index::KEYWORD_INT, 'text' => 'int'],
                    ['line' => 2, 'column' => 13, 'index' => Index::WHITE_SPACE, 'text' => ' '],
                    ['line' => 2, 'column' => 14, 'index' => Index::IDENTIFIER, 'text' => 'a'],
                    ['line' => 2, 'column' => 15, 'index' => Index::COMMA, 'text' => ','],
                    ['line' => 2, 'column' => 16, 'index' => Index::WHITE_SPACE, 'text' => ' '],
                    ['line' => 2, 'column' => 17, 'index' => Index::KEYWORD_INT, 'text' => 'int'],
                    ['line' => 2, 'column' => 20, 'index' => Index::WHITE_SPACE, 'text' => ' '],
                    ['line' => 2, 'column' => 21, 'index' => Index::IDENTIFIER, 'text' => 'b'],
                    ['line' => 2, 'column' => 22, 'index' => Index::CLOSE_PARENTHESIS, 'text' => ')'],
                    ['line' => 2, 'column' => 23, 'index' => Index::COLON, 'text' => ':'],
                    ['line' => 2, 'column' => 24, 'index' => Index::WHITE_SPACE, 'text' => ' '],
                    ['line' => 2, 'column' => 25, 'index' => Index::KEYWORD_INT, 'text' => 'int'],
                    ['line' => 2, 'column' => 28, 'index' => Index::NEW_LINE, 'text' => "\n"],
                    ['line' => 3, 'column' => 1, 'index' => Index::WHITE_SPACE, 'text' => '    '],
                    ['line' => 3, 'column' => 5, 'index' => Index::KEYWORD_RETURN, 'text' => 'return'],
                    ['line' => 3, 'column' => 11, 'index' => Index::WHITE_SPACE, 'text' => ' '],
                    ['line' => 3, 'column' => 12, 'index' => Index::IDENTIFIER, 'text' => 'a'],
                    ['line' => 3, 'column' => 13, 'index' => Index::WHITE_SPACE, 'text' => ' '],
                    ['line' => 3, 'column' => 14, 'index' => Index::PLUS, 'text' => '+'],
                    ['line' => 3, 'column' => 15, 'index' => Index::WHITE_SPACE, 'text' => ' '],
                    ['line' => 3, 'column' => 16, 'index' => Index::IDENTIFIER, 'text' => 'b'],
                    ['line' => 3, 'column' => 17, 'index' => Index::NEW_LINE, 'text' => "\n"],
                ],
            ],

            // Keywords only match whole words, so longer words that start with one are identifiers.
            [
                'file' => 'keyword_prefixes.oc',
                'expected' => [
                    ['line' => 1, 'column' => 1, 'index' => Index::IDENTIFIER, 'text' => 'funcs'],
                    ['line' => 1, 'column' => 6, 'index' => Index::WHITE_SPACE, 'text' => ' '],
                    ['line' => 1, 'column' => 7, 'index' => Index::IDENTIFIER, 'text' => 'returns'],
                    ['line' => 1, 'column' => 14, 'index' => Index::WHITE_SPACE, 'text' => ' '],
                    ['line' => 1, 'column' => 15, 'index' => Index::IDENTIFIER, 'text' => 'integer'],
                    ['line' => 1, 'column' => 22, 'index' => Index::WHITE_SPACE, 'text' => ' '],
                    ['line' => 1, 'column' => 23, 'index' => Index::IDENTIFIER, 'text' => 'int2'],
                    ['line' => 1, 'column' => 27, 'index' => Index::WHITE_SPACE, 'text' => ' '],
                    ['line' => 1, 'column' => 28, 'index' => Index::IDENTIFIER, 'text' => '_return'],
                    ['line' => 1, 'column' => 35, 'index' => Index::WHITE_SPACE, 'text' => ' '],
                    ['line' => 1, 'column' => 36, 'index' => Index::IDENTIFIER, 'text' => 'func_name'],
                    ['line' => 1, 'column' => 45, 'index' => Index::NEW_LINE, 'text' => "\n"],
                ],
            ],

            // Tabs are white space, and a run of mixed tabs and spaces is a single token.
            [
                'file' => 'tabs.oc',
                'expected' => [
                    ['line' => 1, 'column' => 1, 'index' => Index::KEYWORD_FUNC, 'text' => 'func'],
                    ['line' => 1, 'column' => 5, 'index' => Index::WHITE_SPACE, 'text' => "\t"],
                    ['line' => 1, 'column' => 6, 'index' => Index::IDENTIFIER, 'text' => 'add'],
                    ['line' => 1, 'column' => 9, 'index' => Index::OPEN_PARENTHESIS, 'text' => '('],
                    ['line' => 1, 'column' => 10, 'index' => Index::CLOSE_PARENTHESIS, 'text' => ')'],
                    ['line' => 1, 'column' => 11, 'index' => Index::NEW_LINE, 'text' => "\n"],
                    ['line' => 2, 'column' => 1, 'index' => Index::WHITE_SPACE, 'text' => "\t \t"],
                    ['line' => 2, 'column' => 4, 'index' => Index::KEYWORD_RETURN, 'text' => 'return'],
                    ['line' => 2, 'column' => 10, 'index' => Index::WHITE_SPACE, 'text' => ' '],
                    ['line' => 2, 'column' => 11, 'index' => Index::IDENTIFIER, 'text' => 'a'],
                    ['line' => 2, 'column' => 12, 'index' => Index::NEW_LINE, 'text' => "\n"],
                ],
            ],

            // A Windows line ending is a single new line token.
            [
                'file' => 'crlf_line_endings.oc',
                'expected' => [
                    ['line' => 1, 'column' => 1, 'index' => Index::KEYWORD_FUNC, 'text' => 'func'],
                    ['line' => 1, 'column' => 5, 'index' => Index::WHITE_SPACE, 'text' => ' '],
                    ['line' => 1, 'column' => 6, 'index' => Index::IDENTIFIER, 'text' => 'a'],
                    ['line' => 1, 'column' => 7, 'index' => Index::NEW_LINE, 'text' => "\r\n"],
                    ['line' => 2, 'column' => 1, 'index' => Index::WHITE_SPACE, 'text' => '    '],
                    ['line' => 2, 'column' => 5, 'index' => Index::KEYWORD_RETURN, 'text' => 'return'],
                    ['line' => 2, 'column' => 11, 'index' => Index::WHITE_SPACE, 'text' => ' '],
                    ['line' => 2, 'column' => 12, 'index' => Index::IDENTIFIER, 'text' => 'b'],
                    ['line' => 2, 'column' => 13, 'index' => Index::NEW_LINE, 'text' => "\r\n"],
                ],
            ],

            // A carriage return on its own is also a new line.
            [
                'file' => 'cr_line_endings.oc',
                'expected' => [
                    ['line' => 1, 'column' => 1, 'index' => Index::KEYWORD_FUNC, 'text' => 'func'],
                    ['line' => 1, 'column' => 5, 'index' => Index::WHITE_SPACE, 'text' => ' '],
                    ['line' => 1, 'column' => 6, 'index' => Index::IDENTIFIER, 'text' => 'a'],
                    ['line' => 1, 'column' => 7, 'index' => Index::NEW_LINE, 'text' => "\r"],
                    ['line' => 2, 'column' => 1, 'index' => Index::KEYWORD_RETURN, 'text' => 'return'],
                    ['line' => 2, 'column' => 7, 'index' => Index::WHITE_SPACE, 'text' => ' '],
                    ['line' => 2, 'column' => 8, 'index' => Index::IDENTIFIER, 'text' => 'b'],
                    ['line' => 2, 'column' => 9, 'index' => Index::NEW_LINE, 'text' => "\r"],
                ],
            ],
        ];
    }

    /**
     * @throws LogicException
     */
    #[Test]
    #[DataProvider('tokenize_throws_when_a_character_cannot_be_tokenized_provider')]
    public function tokenize_throws_when_a_character_cannot_be_tokenized(
        string $file,
        int $line,
        int $column,
        string $message,
    ): void {
        $tokenizer = new Tokenizer();
        $source = $this->readSourceFile($file);

        try {
            $tokenizer->tokenize($source);
        } catch (SyntaxException $exception) {
            $position = $exception->getPosition();

            $this->assertSame($line, $position->getLine(), sprintf(
                'Expected the exception to be on line %d, but it was on line %d.',
                $line,
                $position->getLine(),
            ));

            $this->assertSame($column, $position->getColumn(), sprintf(
                'Expected the exception to be at column %d, but it was at column %d.',
                $column,
                $position->getColumn(),
            ));

            $this->assertSame($message, $exception->getMessage());

            return;
        }

        $this->fail('Expected a SyntaxException, but the source was tokenized without one.');
    }

    /**
     * @phpstan-return list<SyntaxErrorScenario>
     */
    public static function tokenize_throws_when_a_character_cannot_be_tokenized_provider(): array
    {
        return [
            // An unknown character is reported wherever it appears.
            [
                'file' => 'unexpected_character_at_start.oc',
                'line' => 1,
                'column' => 1,
                'message' => 'Unexpected character "#" on line 1, column 1.',
            ],
            [
                'file' => 'unexpected_character.oc',
                'line' => 2,
                'column' => 14,
                'message' => 'Unexpected character "$" on line 2, column 14.',
            ],

            // A multibyte UTF-8 character is reported whole.
            [
                'file' => 'non_ascii_character.oc',
                'line' => 1,
                'column' => 9,
                'message' => 'Unexpected character "é" on line 1, column 9.',
            ],

            // A byte that is not valid UTF-8 is reported on its own.
            [
                'file' => 'invalid_utf8.oc',
                'line' => 1,
                'column' => 6,
                'message' => "Unexpected character \"\xFF\" on line 1, column 6.",
            ],
        ];
    }

    /**
     * @throws LogicException
     */
    private function readSourceFile(string $file): string
    {
        $path = sprintf('%s/%s', self::SOURCE_FILES_DIRECTORY, $file);
        $source = @file_get_contents($path);

        if ($source === false) {
            throw new LogicException(sprintf(
                'The source file "%s" could not be read.',
                $path,
            ));
        }

        return $source;
    }

    /**
     * @param list<Token> $tokens
     *
     * @return list<TokenShape>
     */
    private function toTokenShapes(array $tokens): array
    {
        return array_map(static fn(Token $token): array => [
            'line' => $token->getPosition()->getLine(),
            'column' => $token->getPosition()->getColumn(),
            'index' => $token->getIndex(),
            'text' => $token->getText(),
        ], $tokens);
    }
}
