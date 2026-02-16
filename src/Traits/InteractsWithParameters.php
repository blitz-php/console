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
     * Get the value of a command argument or option.
     *
     * @param string     $key     Argument or option name
     * @param mixed|null $default Default value if parameter is not defined
     *
     * @return mixed Parameter value or default value
     */
    public function parameter(string $key, mixed $default = null): mixed
    {
        $params = $this->parameters();

        return $params[$key] ?? $default;
    }

    /**
     * Get all command parameters.
     *
     * @return array<string, mixed> Command parameters
     */
    public function parameters(): array
    {
        return array_merge($this->parameters['arguments'], $this->parameters['options']);
    }

    /**
     * Check if an parameter exists.
     *
     * @param string $name Parameter name
     *
     * @return bool True if parameter exists, false otherwise
     */
    public function hasParameter(string $name): bool
    {
        return isset($this->parameters['arguments'][$name]) || isset($this->parameters['options'][$name]);
    }
}
