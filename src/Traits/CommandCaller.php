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

use Dimtrovich\Console\Exceptions\CommandNotFoundException;
use InvalidArgumentException;
use Throwable;

/**
 * Provides command calling capabilities to the console application.
 *
 * This trait allows calling registered commands programmatically with various
 * argument formats, including raw command line strings and structured arrays.
 * It also supports silent execution and output caching.
 *
 * @mixin \Dimtrovich\Console\Console
 */
trait CommandCaller
{
    /**
     * Call a registered command.
     *
     * @param string               $commandName Command name, alias, or FQCN
     * @param array<string, mixed> $arguments   Command arguments (associative or indexed)
     * @param array<string, mixed> $options     Command options (associative)
     *
     * @return mixed Command execution result
     *
     * @throws CommandNotFoundException If the command doesn't exist
     */
    public function call(string $commandName, array $arguments = [], array $options = []): mixed
    {
        $command = $this->retrieveCommand($commandName);
        $action  = $command['action'] ?? null;

        if ($action === null) {
            if (str_contains($commandName, '\\')) {
                $availables = array_keys($this->_commands);
            } else {
                $availables = array_map(fn ($cmd) => $cmd['name'], $this->_commands);
            }

            $this->outputHelper()->showCommandNotFound($commandName, $availables);

            return ($this->onExit)(127);
        }

        foreach ($options as $key => $value) {
            $key = preg_replace('/^\-\-/', '', $key);
            if (! isset($options[$key])) {
                $options[$key] = $value;
            }
        }

        return $action($arguments, $options, true);
    }

    /**
     * Call a command silently, suppressing all output.
     *
     * This method buffers the command output internally without displaying it.
     * The output can later be retrieved using `captureOutput()`.
     *
     * @param string               $command   Command name, alias, or FQCN
     * @param array<string, mixed> $arguments Command arguments (associative or indexed)
     * @param array<string, mixed> $options   Command options (associative)
     *
     * @return mixed Command execution result
     *
     * @throws CommandNotFoundException If the command doesn't exist
     *
     * @example
     * ```php
     * // Clear cache without showing output
     * $app->callSilent('cache:clear');
     *
     * // Run migrations silently in background
     * $app->callSilent('migrate', ['--force' => true]);
     * ```
     */
    public function callSilent(string $command, array $arguments = [], array $options = []): mixed
    {
        ob_start();

        try {
            $result = $this->call($command, $arguments, $options);

            $key = $this->generateCacheKey($command, $arguments, $options);

            // Get buffered output
            $this->commandOutputCache[$key] = ob_get_clean() ?: '';

            return $result;
        } catch (Throwable $e) {
            // Clean buffer on error
            ob_end_clean();

            throw $e;
        }
    }

    /**
     * Execute a command from a raw command line string.
     *
     * Parses a command line string similar to how it would be typed in a terminal
     * and executes the corresponding command.
     *
     * @param string $commandLine Raw command line string (e.g., 'user:create john --email=test@mail.com')
     *
     * @return mixed Command execution result
     *
     * @throws CommandNotFoundException If the command doesn't exist
     * @throws InvalidArgumentException If the command line cannot be parsed
     *
     * @example
     * ```php
     * $app->callRaw('user:create john --email=test@mail.com --active');
     * $app->callRaw('migrate:refresh --force');
     * ```
     */
    public function callRaw(string $commandLine): mixed
    {
        [$command, $arguments, $options] = $this->parseRawCommand($commandLine);

        return $this->call($command, $arguments, $options);
    }

    /**
     * Execute a command silently from a raw command line string.
     *
     * Similar to `callRaw()` but suppresses all output.
     *
     * @param string $commandLine Raw command line string
     *
     * @return mixed Command execution result
     *
     * @throws CommandNotFoundException If the command doesn't exist
     * @throws InvalidArgumentException If the command line cannot be parsed
     *
     * @example
     * ```php
     * $app->callRawSilent('cache:clear --all');
     * ```
     */
    public function callRawSilent(string $commandLine): mixed
    {
        [$command, $arguments, $options] = $this->parseRawCommand($commandLine);

        return $this->callSilent($command, $arguments, $options);
    }

    /**
     * Parse a raw command line string into its components.
     *
     * Splits a command line string into the command name, arguments, and options.
     * Supports quoted strings, escaped characters, and various option formats.
     *
     * @param string $commandLine Raw command line string to parse
     *
     * @return array{string, list<string>, array<string, mixed>} Tuple containing:
     *                                                           - string: The command name
     *                                                           - list<string>: Indexed array of positional arguments
     *                                                           - array<string, mixed>: Associative array of options
     *
     * @throws InvalidArgumentException If the command line cannot be parsed
     *
     * @example
     * ```php
     * // Returns ['user:create', ['John Doe'], ['email' => 'john@mail.com', 'active' => true]]
     * $app->parseRawCommand('user:create "John Doe" --email=john@mail.com --active');
     * ```
     */
    public function parseRawCommand(string $commandLine): array
    {
        $regexString = '([^\s]+?)(?:\s|(?<!\\\\)"|(?<!\\\\)\'|$)';
        $regexQuoted = '(?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\')';

        $tokens = [];
        $length = strlen($commandLine);
        $cursor = 0;

        /**
         * Adapted from Symfony's `StringInput::tokenize()` with modifications.
         *
         * @see https://github.com/symfony/symfony/blob/master/src/Symfony/Component/Console/Input/StringInput.php
         */
        while ($cursor < $length) {
            if (preg_match('/\s+/A', $commandLine, $match, 0, $cursor)) {
                // Ignore spaces
            } elseif (preg_match('/' . $regexQuoted . '/A', $commandLine, $match, 0, $cursor)) {
                $tokens[] = stripcslashes(substr($match[0], 1, strlen($match[0]) - 2));
            } elseif (preg_match('/' . $regexString . '/A', $commandLine, $match, 0, $cursor)) {
                $tokens[] = stripcslashes($match[1]);
            } else {
                throw new InvalidArgumentException(sprintf(
                    'Unable to parse the input near "... %s ...".',
                    substr($commandLine, $cursor, 10)
                ));
            }

            $cursor += strlen($match[0]);
        }

        $command   = array_shift($tokens);
        $arguments = [];
        $options   = [];

        $i     = 0;
        $total = count($tokens);

        while ($i < $total) {
            $token = $tokens[$i];

            // Long option (e.g., --option)
            if (str_starts_with($token, '--')) {
                $optionName = substr($token, 2);

                if (str_contains($optionName, '=')) {
                    [$optionName, $value] = explode('=', $optionName, 2);
                    $options[$optionName] = $this->parseOptionValue($value);
                } elseif ($i + 1 < $total && ! str_starts_with($tokens[$i + 1], '-')) {
                    $options[$optionName] = $this->parseOptionValue($tokens[$i + 1]);
                    $i++;
                } else {
                    $options[$optionName] = true;
                }
            }
            // Short option (e.g., -o)
            elseif (str_starts_with($token, '-') && strlen($token) > 1) {
                $shortOptions = substr($token, 1);
                $optLength    = strlen($shortOptions);

                for ($j = 0; $j < $optLength; $j++) {
                    $optChar = $shortOptions[$j];

                    // Check if this short option has a value
                    // Format: -ovalue or -o value
                    if ($j === $optLength - 1 && $i + 1 < $total && ! str_starts_with($tokens[$i + 1], '-')) {
                        // Last character, can have a separated value
                        $options[$optChar] = $this->parseOptionValue($tokens[$i + 1]);
                        $i++;
                    } elseif ($j < $optLength - 1 && ctype_alpha($shortOptions[$j + 1]) && ! isset($tokens[$i + 1])) {
                        // Next character is a letter, it's a flag
                        $options[$optChar] = true;
                    } else {
                        $options[$optChar] = true;
                    }
                }
            }
            // Simple argument
            else {
                $arguments[] = $token;
            }

            $i++;
        }

        return [$command, $arguments, $options];
    }

    /**
     * Parse and type-cast an option value.
     *
     * Automatically detects and converts boolean strings, numeric values,
     * null literals, and removes surrounding quotes.
     *
     * @param string $value Raw option value string
     *
     * @return mixed Type-casted value (bool|int|float|string|null)
     *
     * @example
     * ```php
     * $this->parseOptionValue('true');   // returns true
     * $this->parseOptionValue('42');      // returns 42 (int)
     * $this->parseOptionValue('3.14');    // returns 3.14 (float)
     * $this->parseOptionValue('null');    // returns null
     * $this->parseOptionValue('"hello"'); // returns 'hello'
     * ```
     */
    private function parseOptionValue(string $value): mixed
    {
        // Clear quotes
        if (strlen($value) >= 2
            && (($value[0] === '"' && $value[-1] === '"')
             || ($value[0] === "'" && $value[-1] === "'"))) {
            $value = substr($value, 1, -1);
        }

        // Type detection
        $lower = strtolower($value);

        if ($lower === 'true' || $lower === 'false') {
            return $lower === 'true';
        }

        if ($lower === 'null') {
            return null;
        }

        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        return $value;
    }
}
