<?php

declare(strict_types=1);

/**
 * This file is part of Dimtrovich - Console.
 *
 * (c) 2026 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Dimtrovich\Console\Traits;

use Ahc\Cli\Input\Reader;
use Ahc\Cli\IO\Interactor;
use InvalidArgumentException;

use function Ahc\Cli\t;

/**
 * Provides interaction with user input.
 *
 * @property Interactor $io
 * @property Reader     $reader
 *
 * @mixin \Dimtrovich\Console\Command
 */
trait InteractsWithInput
{
    /**
     * Prompt the user for input.
     *
     * @param string        $text    Prompt text
     * @param mixed|null    $default Default value
     * @param callable|null $fn      Validator/sanitizer callback
     * @param int           $retry   Number of retries on failure
     *
     * @return mixed User input
     */
    public function prompt(string $text, $default = null, ?callable $fn = null, int $retry = 3): mixed
    {
        return $this->io->prompt($text, $default, $fn, $retry);
    }

    /**
     * Prompt the user for hidden input (like password).
     *
     * @param string        $text  Prompt text
     * @param callable|null $fn    Validator/sanitizer callback
     * @param int           $retry Number of retries on failure
     *
     * @return mixed User input
     */
    public function promptHidden(string $text, ?callable $fn = null, int $retry = 3): mixed
    {
        return $this->io->promptHidden($text, $fn, $retry);
    }

    /**
     * Ask the user for input (alias of prompt).
     *
     * @param string     $question Question text
     * @param mixed|null $default  Default value
     *
     * @return mixed User input
     */
    public function ask(string $question, mixed $default = null): mixed
    {
        return $this->prompt($question, $default);
    }

    /**
     * Ask the user for secret input (alias of promptHidden).
     *
     * @param string        $text  Prompt text
     * @param callable|null $fn    Validator/sanitizer callback
     * @param int           $retry Number of retries on failure
     *
     * @return mixed User input
     */
    public function secret(string $text, ?callable $fn = null, int $retry = 3): mixed
    {
        return $this->promptHidden($text, $fn, $retry);
    }

    /**
     * Ask with auto-completion from given choices.
     *
     * @param string       $question Prompt question
     * @param list<string> $choices  Available choices
     * @param mixed|null   $default  Default value
     *
     * @return mixed User input
     */
    public function askWithCompletion(string $question, array $choices, mixed $default = null): mixed
    {
        return $this->prompt($question, $default, function ($input) use ($choices) {
            if (! in_array($input, $choices, true)) {
                throw new InvalidArgumentException(
                    t('Value must be one of: %s', [implode(', ', $choices)])
                );
            }

            return $input;
        });
    }

    /**
     * Let the user make a single choice from available choices.
     *
     * @param string       $question Prompt question
     * @param list<string> $choices  Available choices
     * @param mixed|null   $default  Default value if not chosen or invalid
     * @param bool         $case     Whether user input should be case-sensitive
     * @param bool         $optional Whether the choice is optional
     * @param 'key'|'value'|'both'|null $returnType What to return
     *
     * @return mixed User choice based on return type or default value
     * 
     * @example
     * // Indexed array (numeric keys)
     * $fruit = $this->choice(['orange', 'apple', 'pinaple']);
     * // User enters "2" -> returns "pinaple" (value by default)
     * 
     * @example
     * // Associative array
     * $juice = $this->choice([
     *     'orange'  => "Orange juice",
     *     'apple'   => "Apple juice", 
     *     'pinaple' => "Pinaple juice"
     * ]);
     * // User enters "2" -> returns "pinaple" (key by default for associative arrays)
     * 
     * @example
     * // Force return of value even for associative array
     * $juice = $this->choice([
     *     'orange'  => "Orange juice",
     *     'apple'   => "Apple juice", 
     *     'pinaple' => "Pinaple juice"
     * ], returnType: 'value');
     * // User enters "2" -> returns "Pinaple juice"
     */
    public function choice(string $question, array $choices, $default = null, bool $case = false, bool $optional = false, ?string $returnType = null): mixed
    {
        // Get automatically default return type
        if (func_num_args() <= 5 || $returnType === null) {
            $isAssociative = array_keys($choices) !== range(0, count($choices) - 1);
            $returnType    = $isAssociative ? 'key' : 'value';
        }

        $this->writer->question($question)->eol();

        foreach ($choices as $key => $value) {
            $this->writer->choice(str_pad("  [{$key}] ", 6))->answer($value)->eol();
        }

        $choice = $this->prompt(t('Choice'), retry: $optional ? 0 : 3);

        return $this->validChoice($choice, $choices, $default, $case, $returnType);
    }

    /**
     * Let the user make multiple choices from available choices.
     *
     * @param string       $question Prompt question
     * @param list<string> $choices  Available choices
     * @param mixed|null   $default  Default value if not chosen or invalid
     * @param bool         $case     Whether user input should be case-sensitive
     * @param bool         $optional Whether the choice is optional
     * @param 'key'|'value'|'both'|null $returnType What to return
     *
     * @return list<mixed> User choices based on return type or default values
     * 
     * @example
     * // Indexed array - returns values by default
     * $fruits = $this->choices(['orange', 'apple', 'pinaple']);
     * // User enters "1,2" -> returns ['apple', 'pinaple']
     * 
     * @example
     * // Associative array - returns keys by default
     * $juice = $this->choice([
     *     'orange'  => "Orange juice",
     *     'apple'   => "Apple juice", 
     *     'pinaple' => "Pinaple juice"
     * ]);
     * // User enters "0,2" -> returns ['orange', 'pinaple']
     */
    public function choices(string $question, array $choices, $default = null, bool $case = false, bool $optional = false, ?string $returnType = null): array
    {
        // Get automatically default return type
        if (func_num_args() <= 5 || $returnType === null) {
            $isAssociative = array_keys($choices) !== range(0, count($choices) - 1);
            $returnType    = $isAssociative ? 'key' : 'value';
        }

        $this->writer->question($question)->eol();

        $keys = array_keys($choices);
        $maxKeyLength = max(array_map('strlen', array_map('strval', $keys)));

        foreach ($choices as $key => $value) {
            $formattedKey = str_pad("[{$key}]", $maxKeyLength + 2, ' ');
            $this->writer->choice("  {$formattedKey} ")->answer($value)->eol();
        }

        $choice = $this->prompt(t('Choices (comma separated)'), retry: $optional ? 0 : 3);

        if (is_string($choice)) {
            $choice = explode(',', str_replace(' ', '', $choice));
        }

        $valid = array_map(fn ($option) => $this->validChoice($option, $choices, $default, $case, $returnType), $choice);

        return array_values(array_unique(array_filter($valid)));
    }

    /**
     * Confirm if the user accepts a question.
     *
     * @param string $question Question text
     * @param string $default  Default answer ('y' or 'n')
     *
     * @return bool True if accepted, false otherwise
     */
    public function confirm(string $question, string $default = 'y'): bool
    {
        return $this->io->confirm($question, $default);
    }

    /**
     * Validate a choice against available choices.
     *
     * @param mixed                    $input      User input
     * @param array<string|int, mixed> $choices    Available choices
     * @param mixed|null               $default    Default value
     * @param bool                     $case       Whether comparison should be case-sensitive
     * @param 'key'|'value'|'both'     $returnType What to return
     *
     * @return mixed Validated choice or default value
     */
    protected function validChoice($input, array $choices, mixed $default, bool $case, string $returnType): mixed
    {
        if (array_key_exists($input, $choices)) {
            return $this->formatChoiceResult($input, $choices[$input], $returnType);
        }

        $fn = $case ? 'strcmp' : 'strcasecmp';
        foreach ($choices as $key => $value) {
            if ($fn((string) $input, (string) $value) === 0) {
                return $this->formatChoiceResult($key, $value, $returnType);
            }
        }

        if (array_is_list($choices) && is_numeric($input)) {
            $position = (int) $input - 1;
            if (isset($choices[$position])) {
                $key   = $position;
                $value = $choices[$position];
                return $this->formatChoiceResult($key, $value, $returnType);
            }
        }

        if ($default !== null) {
            // Default can be da key or a valur
            if (array_key_exists($default, $choices)) {
                return $this->formatChoiceResult($default, $choices[$default], $returnType);
            }
            
            // Find default like value
            foreach ($choices as $key => $value) {
                if ($value === $default) {
                    return $this->formatChoiceResult($key, $value, $returnType);
                }
            }
        }

        return null;
    }

    /**
     * Format the choice result based on return type.
     *
     * @param string|int            $key        Selected key
     * @param mixed                 $value      Selected value
     * @param 'key'|'value'|'both'  $returnType Desired return type
     *
     * @return mixed Formatted result
     */
    protected function formatChoiceResult(string|int $key, mixed $value, string $returnType): mixed
    {
        if (! in_array($returnType, ['key', 'value', 'both'])) {
            throw new InvalidArgumentException();
        }

        return match ($returnType) {
            'key'   => $key,
            'value' => $value,
            'both'  => ['key' => $key, 'value' => $value],
        };
    }
}
