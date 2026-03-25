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

/**
 * Provides interaction with command parameters (arguments and options).
 *
 * @mixin \Dimtrovich\Console\Command
 */
trait InteractsWithParameters
{
    /**
     * Parameters received after command execution.
     *
     * @var array{
     *      arguments: array<string, mixed>,
     *      options: array<string, mixed>
     * }
     */
    private array $parameters = [];

    /**
     * Cached merged parameters.
     *
     * @var array<string, mixed>
     */
    private array $cachedParameters = [];

    /**
     * Define parameters received after command execution.
     *
     * @internal
     *
     * @param array<string, mixed> $arguments Command arguments
     * @param array<string, mixed> $options   Command options
     */
    public function setParameters(array $arguments, array $options): void
    {
        $this->parameters = [
            'arguments' => $arguments,
            'options'   => $options,
        ];

        $this->cachedParameters = [];
    }

    /**
     * Get the value of a command argument.
     *
     * @param string     $name    Argument name
     * @param mixed|null $default Default value if argument is not defined
     *
     * @return mixed Argument value or default value
     */
    public function argument(string $name, mixed $default = null): mixed
    {
        return $this->parameters['arguments'][$name] ?? $default;
    }

    /**
     * Get all command arguments.
     *
     * @return array<string, mixed> Command arguments
     */
    public function arguments(): array
    {
        return $this->parameters['arguments'];
    }

    /**
     * Check if an argument exists.
     *
     * @param string $name Argument name
     *
     * @return bool True if argument exists, false otherwise
     */
    public function hasArgument(string $name): bool
    {
        return isset($this->parameters['arguments'][$name]);
    }

    /**
     * Check if any of the given arguments exist.
     *
     * @param string ...$names Argument names
     *
     * @return bool True if at least one argument exists, false otherwise
     */
    public function hasAnyArguments(string ...$names): bool
    {
        foreach ($names as $name) {
            if ($this->hasArgument($name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if all of the given arguments exist.
     *
     * @param string ...$names Argument names
     *
     * @return bool True if all arguments exist, false otherwise
     */
    public function hasAllArguments(string ...$names): bool
    {
        foreach ($names as $name) {
            if (! $this->hasArgument($name)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if any of the given arguments are missing.
     *
     * @param string ...$names Argument names
     *
     * @return bool True if at least one argument is missing, false otherwise
     */
    public function missingAnyArguments(string ...$names): bool
    {
        return ! $this->hasAllArguments(...$names);
    }

    /**
     * Check if all of the given arguments are missing.
     *
     * @param string ...$names Argument names
     *
     * @return bool True if all arguments are missing, false otherwise
     */
    public function missingAllArguments(string ...$names): bool
    {
        return ! $this->hasAnyArguments(...$names);
    }

    /**
     * Merge additional arguments with existing ones.
     *
     * @param array<string, mixed> $arguments Arguments to merge
     */
    public function mergeArguments(array $arguments): void
    {
        $this->parameters['arguments'] = array_merge(
            $this->parameters['arguments'],
            $arguments
        );

        $this->cachedParameters = [];
    }

    /**
     * Get the value of a command option.
     *
     * @param string     $name    Option name
     * @param mixed|null $default Default value if option is not defined
     *
     * @return mixed Option value or default value
     */
    public function option(string $name, mixed $default = null): mixed
    {
        return $this->parameters['options'][$name] ?? $default;
    }

    /**
     * Get all command options.
     *
     * @return array<string, mixed> Command options
     */
    public function options(): array
    {
        return $this->parameters['options'];
    }

    /**
     * Check if an option exists.
     *
     * @param string $name Option name
     *
     * @return bool True if option exists, false otherwise
     */
    public function hasOption(string $name): bool
    {
        return isset($this->parameters['options'][$name]);
    }

    /**
     * Check if any of the given options exist.
     *
     * @param string ...$names Option names
     *
     * @return bool True if at least one option exists, false otherwise
     */
    public function hasAnyOptions(string ...$names): bool
    {
        foreach ($names as $name) {
            if ($this->hasOption($name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if all of the given options exist.
     *
     * @param string ...$names Option names
     *
     * @return bool True if all options exist, false otherwise
     */
    public function hasAllOptions(string ...$names): bool
    {
        foreach ($names as $name) {
            if (! $this->hasOption($name)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if any of the given options are missing.
     *
     * @param string ...$names Option names
     *
     * @return bool True if at least one option is missing, false otherwise
     */
    public function missingAnyOptions(string ...$names): bool
    {
        return ! $this->hasAllOptions(...$names);
    }

    /**
     * Check if all of the given options are missing.
     *
     * @param string ...$names Option names
     *
     * @return bool True if all options are missing, false otherwise
     */
    public function missingAllOptions(string ...$names): bool
    {
        return ! $this->hasAnyOptions(...$names);
    }

    /**
     * Merge additional options with existing ones.
     *
     * @param array<string, mixed> $options Options to merge
     */
    public function mergeOptions(array $options): void
    {
        $this->parameters['options'] = array_merge(
            $this->parameters['options'],
            $options
        );

        $this->cachedParameters = [];
    }

    /**
     * Get the value of a command argument or option.
     *
     * @param string     $key     Argument or option name
     * @param mixed|null $default Default value if parameter is not defined
     *
     * @return mixed Parameter value or default value
     */
    public function parameter(string $key, mixed $default = null): mixed
    {
        return $this->parameters['arguments'][$key]
            ?? $this->parameters['options'][$key]
            ?? $default;
    }

    /**
     * Get all command parameters.
     *
     * Note: Arguments take precedence over options with the same name.
     *
     * @return array<string, mixed> Command parameters
     */
    public function parameters(): array
    {
        if ($this->cachedParameters === []) {
            $this->cachedParameters = array_merge(
                $this->parameters['arguments'],
                $this->parameters['options']
            );
        }

        return $this->cachedParameters;
    }

    /**
     * Check if a parameter exists.
     *
     * @param string $name Parameter name
     *
     * @return bool True if parameter exists, false otherwise
     */
    public function hasParameter(string $name): bool
    {
        return isset($this->parameters['arguments'][$name])
            || isset($this->parameters['options'][$name]);
    }

    /**
     * Check if any of the given parameters exist.
     *
     * @param string ...$names Parameter names
     *
     * @return bool True if at least one parameter exists, false otherwise
     */
    public function hasAnyParameters(string ...$names): bool
    {
        foreach ($names as $name) {
            if ($this->hasParameter($name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if all of the given parameters exist.
     *
     * @param string ...$names Parameter names
     *
     * @return bool True if all parameters exist, false otherwise
     */
    public function hasAllParameters(string ...$names): bool
    {
        foreach ($names as $name) {
            if (! $this->hasParameter($name)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if any of the given parameters are missing.
     *
     * @param string ...$names Parameter names
     *
     * @return bool True if at least one parameter is missing, false otherwise
     */
    public function missingAnyParameters(string ...$names): bool
    {
        return ! $this->hasAllParameters(...$names);
    }

    /**
     * Check if all of the given parameters are missing.
     *
     * @param string ...$names Parameter names
     *
     * @return bool True if all parameters are missing, false otherwise
     */
    public function missingAllParameters(string ...$names): bool
    {
        return ! $this->hasAnyParameters(...$names);
    }
}
