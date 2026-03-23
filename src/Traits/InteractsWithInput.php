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
                    t('Value must be one of: %s', [implode(', ', $choices)]),
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
     *
     * @return mixed User choice or default value
     */
    public function choice(string $question, array $choices, $default = null, bool $case = false): mixed
    {
        $this->writer->question($question)->eol();

        foreach ($choices as $key => $value) {
            $this->writer->choice(str_pad("  [{$key}] ", 6))->answer($value)->eol();
        }

        $choice = $this->prompt(t('Choice'));

        return $this->validChoice($choice, $choices, $default, $case);
    }

    /**
     * Let the user make multiple choices from available choices.
     *
     * @param string       $question Prompt question
     * @param list<string> $choices  Available choices
     * @param mixed|null   $default  Default value if not chosen or invalid
     * @param bool         $case     Whether user input should be case-sensitive
     *
     * @return list<string> User choices or default values
     */
    public function choices(string $question, array $choices, $default = null, bool $case = false): array
    {
        $this->writer->question($question)->eol();

        foreach ($choices as $key => $value) {
            $this->writer->choice(str_pad("  [{$key}] ", 6))->answer($value)->eol();
        }

        $choice = $this->prompt(t('Choices (comma separated)'));

        if (is_string($choice)) {
            $choice = explode(',', str_replace(' ', '', $choice));
        }

        $valid = array_map(fn ($option) => $this->validChoice($option, $choices, $default, $case), $choice);

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
     * @param mixed        $choice  User choice
     * @param list<string> $choices Available choices
     * @param mixed|null   $default Default value
     * @param bool         $case    Whether comparison should be case-sensitive
     *
     * @return mixed Validated choice or default value
     */
    protected function validChoice($choice, array $choices, mixed $default, bool $case): mixed
    {
        if (array_key_exists($choice, $choices)) {
            return $choices[$choice];
        }

        $fn = ['\strcasecmp', '\strcmp'][(int) $case];

        foreach ($choices as $option) {
            if ($fn($choice, $option) === 0) {
                return $option;
            }
        }

        return $choices[$default] ?? $default;
    }
}
