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

namespace Dimtrovich\Console;

use Ahc\Cli\Helper\Terminal;
use Ahc\Cli\Input\Reader;
use Ahc\Cli\IO\Interactor;
use Ahc\Cli\Output\Color;
use Ahc\Cli\Output\Writer;
use Dimtrovich\Console\Exceptions\CommandNotFoundException;
use Dimtrovich\Console\Overrides\Command as BaseCommand;
use Dimtrovich\Console\Overrides\Cursor;
use Dimtrovich\Console\Traits\AdvancedFeatures;
use Dimtrovich\Console\Traits\InteractsWithParameters;
use InvalidArgumentException;

use function Ahc\Cli\t;

/**
 * Base class for creating console commands.
 *
 * @method string alias() Get the command alias.
 * @method string name()  Get the command name.
 */
abstract class Command
{
    use AdvancedFeatures;
    use InteractsWithParameters;

    /**
     * The group under which the command is grouped in the command list.
     */
    protected string $group = '';

    /**
     * The command name.
     */
    protected string $name;

    /**
     * The short description of the command.
     */
    protected string $description = '';

    /**
     * Command usage.
     */
    protected string $usage = '';

    /**
     * Command options.
     *
     * @var array<string, array{
     *      0: string,
     *      1?: mixed,
     *      2?: callable|null
     * }>
     *
     * @example ['option' => ['description', default_value, filter]]
     */
    protected array $options = [];

    /**
     * Command arguments.
     *
     * @var array<string, array{
     *      0: string,
     *      1?: mixed
     * }>
     *
     * @example ['argument' => ['description', default_value]]
     */
    protected array $arguments = [];

    /**
     * The command alias.
     */
    protected string $alias = '';

    /**
     * The command version.
     */
    protected string $version = '';

    /**
     * Console application instance.
     */
    protected Console $app;

    /**
     * Interactor instance.
     */
    protected Interactor $io;

    /**
     * Writer instance.
     */
    protected Writer $writer;

    /**
     * Reader instance.
     */
    protected Reader $reader;

    /**
     * Color instance.
     */
    protected Color $color;

    /**
     * Cursor instance.
     */
    protected Cursor $cursor;

    /**
     * Terminal instance.
     */
    protected Terminal $terminal;

    /**
     * Actual execution of the command.
     *
     * This method must be implemented by classes extending Command
     * to define the behavior of the command when executed.
     *
     * @return mixed
     */
    abstract public function handle();

    /**
     * Initialize necessary properties.
     *
     * @internal
     *
     * @param Console $app Console application instance
     *
     * @return BaseCommand Configured command instance
     */
    public function initialize(Console $app): BaseCommand
    {
        $this->app      = $app;
        $this->io       = $this->app->io();
        $this->writer   = $this->io->writer();
        $this->reader   = $this->io->reader();
        $this->color    = $this->writer->colorizer();
        $this->cursor   = new Cursor();
        $this->terminal = $this->writer->terminal();

        return $this->createCommand();
    }

    /**
     * Call another command.
     *
     * @param string               $command   Command name to call
     * @param array<string, mixed> $arguments Command arguments
     * @param array<string, mixed> $options   Command options
     *
     * @return mixed Command execution result
     *
     * @throws CommandNotFoundException If command doesn't exist
     */
    public function call(string $command, array $arguments = [], array $options = []): mixed
    {
        return $this->app->call($command, $arguments, $options);
    }

    /**
     * Check if a command exists in the registered commands list.
     *
     * @param string $commandName Command name to check
     *
     * @return bool True if command exists, false otherwise
     */
    public function commandExists(string $commandName): bool
    {
        return $this->app->commandExists($commandName);
    }

    /**
     * Pad a string with spaces for alignment.
     *
     * @param string $item   String to pad
     * @param int    $max    Maximum length
     * @param int    $extra  Extra spaces to add
     * @param int    $indent Indentation level
     *
     * @return string Padded string
     */
    public function pad(string $item, int $max, int $extra = 2, int $indent = 0): string
    {
        $max += $extra + $indent;

        return str_pad(str_repeat(' ', $indent) . $item, $max);
    }

    /**
     * Magic method to handle dynamic property access.
     *
     * This method allows accessing protected properties like 'name' and 'alias'
     * as if they were methods (e.g., $command->name()).
     *
     * @param string $name      The property name being accessed as a method
     * @param array  $arguments Method arguments (not used, maintained for signature compatibility)
     *
     * @return mixed The property value if it exists, empty string if property is null
     *
     * @throws InvalidArgumentException If the property does not exist on the class
     *
     * @example
     * ```php
     * $name = $command->name(); // Returns the command name
     * $alias = $command->alias(); // Returns the command alias
     * ```
     */
    public function __call(string $name, array $arguments = [])
    {
        if (property_exists($this, $name)) {
            return $this->{$name} ?? '';
        }

        throw new InvalidArgumentException(t('Undefined method "%s" called.', [$name]));
    }

    /**
     * Create the base command instance.
     *
     * @return BaseCommand Configured command instance
     */
    private function createCommand(): BaseCommand
    {
        $command = new BaseCommand(
            $this->name,
            $this->description,
            false,
            $this->app,
        );

        $this->configure($command);

        return $command;
    }

    /**
     * Configure the command by setting its options, arguments, usage, version, etc.
     *
     * This method can be overridden by classes extending Command
     * to customize the command configuration.
     *
     * @param BaseCommand $command Command instance to configure
     */
    protected function configure(BaseCommand $command): void
    {
        $command->inGroup($this->group)
            ->usage($this->usage)
            ->version($this->version)
            ->alias($this->alias);

        $this->defineOptions($command);
        $this->defineArguments($command);
    }

    /**
     * Define command options.
     *
     * @param BaseCommand $command Command instance
     */
    protected function defineOptions(BaseCommand $command): void
    {
        foreach ($this->options as $option => $value) {
            $value = (array) $value;

            $description = $value[0];
            if (! is_string($description)) {
                continue;
            }

            $default = $value[1] ?? null;
            $filter  = $value[2] ?? null;

            if ($filter !== null && ! is_callable($filter)) {
                $filter = null;
            }

            $command->option($option, $description, $filter, $default);
        }
    }

    /**
     * Define command arguments.
     *
     * @param BaseCommand $command Command instance
     */
    protected function defineArguments(BaseCommand $command): void
    {
        foreach ($this->arguments as $argument => $value) {
            $value = (array) $value;

            $description = $value[0];
            if (! is_string($description)) {
                continue;
            }

            $default = $value[1] ?? null;

            $command->argument($argument, $description, $default);
        }
    }
}
