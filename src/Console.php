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

use Ahc\Cli\Application;
use BlitzPHP\Contracts\Container\ContainerInterface;
use Dimtrovich\Console\Components\Logger;
use Dimtrovich\Console\Exceptions\CommandNotFoundException;
use Dimtrovich\Console\Exceptions\InvalidCommandException;
use Dimtrovich\Console\Traits\CommandCaller;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Throwable;

use function Ahc\Cli\t;

/**
 * Core console application.
 */
class Console extends Application
{
    use CommandCaller;

    /**
     * Container instance.
     */
    protected ?ContainerInterface $container = null;

    /**
     * Logger configuration.
     *
     * @var array{instance: LoggerInterface|null, prefix: string}
     */
    protected array $logger = ['instance' => null, 'prefix' => ''];

    /**
     * @var array<string, callable>
     */
    protected array $hooks = [];

    /**
     * Flags
     *
     * @var array<string, bool>
     */
    protected array $flags = [
        'debug'  => false,
        'header' => true,
        'footer' => false,
    ];

    /**
     * Header title.
     */
    protected string $headtitle = '';

    /**
     * Registered commands.
     *
     * @var array<string, array{
     *      'action' => callable,}>
     *      'name'   => string,
     *      'alias'  => string,
     * }>
     *
     * @example [
     *     'App\Console\Commands\ExampleCommand' => [
     *         'action' => callable,
     *         'name'   => 'example:command',
     *         'alias'  => 'ex:cmd'
     *     ],
     */
    protected array $_commands = [];

    /**
     * Command cache for quick retrieval by name or alias.
     *
     * @var array<string, array>
     */
    protected array $commandCached = [];

    /**
     * Cache of executed command outputs.
     *
     * Keys are generated from command name, arguments and options.
     * Values are the captured stdout/stderr of the commands.
     * This cache prevents re-executing the same command with identical parameters.
     *
     * @var array<string, string>
     */
    protected array $commandOutputCache = [];

    /**
     * Create a new console application.
     *
     * @param string $name    Application name
     * @param string $version Application version
     */
    public function __construct(string $name = '', string $version = '1.0.0')
    {
        parent::__construct($name, $version);

        $this->onException([$this, 'onError']);
    }

    /**
     * Set the container instance.
     */
    public function setContainer(ContainerInterface $container): self
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Set the PSR logger instance for the application.
     *
     * This method configures the PSR-3 logger that will be used by all commands
     * through the `$this->log()` method. It also sets up the Logger component
     * with the console writer and default prefix.
     *
     * @param LoggerInterface $logger The PSR-3 logger instance
     * @param string          $prefix Optional default prefix for all log messages
     *                                (e.g., 'APP' will produce '[APP] Message')
     *
     * @return self The current instance for method chaining
     *
     * @example
     * ```php
     * $console->setLogger($monologLogger, 'APP');
     * ```
     *
     * @see \Dimtrovich\Console\Components\Logger::configure()
     */
    public function setLogger(LoggerInterface $logger, string $prefix = ''): self
    {
        $this->logger['instance'] = $logger;
        $this->logger['prefix']   = $prefix;

        Logger::configure($this->io()->writer(), $logger, $prefix);

        return $this;
    }

    /**
     * Set a hook callback.
     *
     * @param string   $hook Hook name ('before' or 'after')
     * @param callable $fn   Callback function
     */
    public function setHook(string $hook, callable $fn): self
    {
        $this->hooks[$hook] = $fn;

        return $this;
    }

    /**
     * Set a flag value.
     *
     * @param string $flag  Flag name
     * @param bool   $value Flag value
     */
    public function setFlag(string $flag, bool $value): self
    {
        $this->flags[$flag] = $value;

        return $this;
    }

    /**
     * Set the header title.
     */
    public function headtitle(string $headtitle): self
    {
        $this->headtitle = $headtitle;

        return $this;
    }

    /**
     * Capture the output of a command execution.
     * This method executes a command and returns its output as a string.
     * Useful for capturing output of commands when called programmatically.
     * Note: This method will execute the command only once per unique set of arguments and options,
     * and cache the output for subsequent calls with the same parameters.
     *
     * Example usage:
     * ```php
     * // Capture output of a command
     * $output = $app->captureOutput('list:users', ['--active' => true]);
     * echo $output;
     * ```
     *
     * @param string               $command   Command name or FQCN
     * @param array<string, mixed> $arguments Command arguments
     * @param array<string, mixed> $options   Command options
     *
     * @return string Captured output from the command execution
     *
     * @throws CommandNotFoundException If command doesn't exist
     */
    public function captureOutput(string $command, array $arguments = [], array $options = []): string
    {
        $key = $this->generateCacheKey($command, $arguments, $options);

        if (isset($this->commandOutputCache[$key])) {
            return $this->commandOutputCache[$key];
        }

        $this->callSilent($command, $arguments, $options);

        return $this->commandOutputCache[$key] ?? '';
    }

    /**
     * Clear command output cache.
     *
     * @param string|null $command Specific command name to clear (null = clear all)
     */
    public function clearOutputCache(?string $command = null): self
    {
        if ($command === null) {
            $this->commandOutputCache = [];

            return $this;
        }

        foreach (array_keys($this->commandOutputCache) as $key) {
            if (str_starts_with($key, md5($command))) {
                unset($this->commandOutputCache[$key]);
            }
        }

        return $this;
    }

    /**
     * Check if a command has already been executed with these parameters.
     *
     * @param string               $command   Command name or FQCN
     * @param array<string, mixed> $arguments Command arguments
     * @param array<string, mixed> $options   Command options
     *
     * @return bool True if command has already been executed
     */
    public function hasExecuted(string $command, array $arguments = [], array $options = []): bool
    {
        $key = $this->generateCacheKey($command, $arguments, $options);

        return isset($this->commandOutputCache[$key]);
    }

    /**
     * Check if a command exists in the registered commands list.
     *
     * @param string $commandName Command name
     *
     * @return bool True if command exists, false otherwise
     */
    public function commandExists(string $commandName): bool
    {
        $command = $this->retrieveCommand($commandName);

        return ! empty($command) && is_callable($command['action']);
    }

    /**
     * Add a command to the console.
     *
     * @param class-string<Command> $className Command FQCN
     *
     * @throws InvalidCommandException If command is not valid
     */
    public function addCommand(string $className): self
    {
        $class = new ReflectionClass($className);

        if (! $class->isInstantiable() || ! $class->isSubclassOf(Command::class)) {
            throw new InvalidCommandException($className);
        }

        /** @var Command $instance */
        $instance = $this->container !== null ? $this->container->make($className) : $class->newInstance();

        return $this->addCommandInstance($instance);
    }

    /**
     * Add a command instance to the console.
     *
     * This method allows adding a fully instantiated command object,
     * which is useful for commands with complex constructor dependencies
     * or when you need to configure the command before registration.
     *
     * @param Command $instance The command instance to add
     *
     * @return self The current instance for method chaining
     *
     * @example
     * ```php
     * // Command with dependencies
     * $command = new DatabaseCommand($db, $logger);
     * $console->addCommandInstance($command);
     *
     * // Pre-configured command
     * $command = new CustomCommand();
     * $command->setEnvironment('production');
     * $console->addCommandInstance($command);
     * ```
     */
    public function addCommandInstance(Command $instance): self
    {
        $command = $instance->initialize($this);

        $app    = $this;
        $action = function (?array $arguments = [], ?array $options = [], ?bool $suppress = false) use ($instance, $command, $app) {
            $this->name();

            $args       = $command->args();
            $argsNames  = array_keys($args);
            $parameters = $command->values();
            $arguments  = $arguments === [] || $arguments === null ? $args : $arguments;
            $options    = $options === []   || $options === null ? array_diff_key($parameters, $arguments) : $options;

            foreach ($arguments as $key => $value) {
                if (is_int($key)) {
                    $key = $argsNames[$key] ?? $key;
                }

                $key             = is_string($key) ? $app->kebabize($key) : $key;
                $arguments[$key] = $value;
            }

            foreach ($options as $key => $value) {
                $options[$app->kebabize($key)] = $value;
            }

            $instance->setParameters($arguments, $options);

            $app->before($suppress === true, $instance);

            $result = $app->container
                ? $app->container->call([$instance, 'handle'])
                : $instance->handle();

            $app->after($suppress === true, $instance);

            return $result;
        };

        $this->_commands[$instance::class] = [
            'action' => $action,
            'name'   => $instance->name(),
            'alias'  => $instance->alias(),
        ];

        $this->add($command->action($action));

        return $this;
    }

    /**
     * Hook executed before command execution.
     *
     * @param bool    $suppress Whether output is suppressed
     * @param Command $command  Command instance
     */
    protected function before(bool $suppress, Command $command): void
    {
        if (isset($this->hooks['before'])) {
            ($this->hooks['before'])($suppress, $command);
        }
    }

    /**
     * Hook executed after command execution.
     *
     * @param bool    $suppress Whether output is suppressed
     * @param Command $command  Command instance
     */
    protected function after(bool $suppress, Command $command): void
    {
        if (isset($this->hooks['after'])) {
            ($this->hooks['after'])($suppress, $command);
        }
    }

    /**
     * Error handler for exceptions.
     *
     * @param Throwable $e        Exception
     * @param int       $exitCode Exit code
     */
    protected function onError(Throwable $e, int $exitCode): void
    {
        if ($this->logger['instance'] !== null) {
            $name = $this->name() ?: 'console';
            $name .= $this->logger['prefix'] ? ' > ' . $this->logger['prefix'] : '';

            $this->logger['instance']->error('[' . $name . '] ' . $e->getMessage(), ['exception' => $e]);
        }

        if ($this->flags['debug']) {
            $this->outputHelper()->printTrace($e);
        } else {
            $this->io()->error($e->getMessage(), true);
        }

        exit($exitCode);
    }

    /**
     * Show default help screen.
     *
     * @override
     */
    public function showDefaultHelp(): mixed
    {
        $writer = $this->io()->writer();

        $header = ! $this->flags['header'] ? '' : (
            $this->headtitle ? "\n{$this->headtitle}" : "\n{$this->name}, " . t('version') . " {$this->version}"
        );

        $footer = ! $this->flags['footer'] ? '' : t('Run `<command> --help` for specific help');

        if ($this->logo) {
            $writer->logo($this->logo, true);
        }

        $this->outputHelper()->showCommandsHelp($this->commands(), $header, $footer);

        return ($this->onExit)();
    }

    /**
     * Set the default command.
     *
     * @param string $commandName The name or FQCN of the default command
     *
     * @throws InvalidArgumentException If the specified command name does not exist
     *
     * @override
     */
    public function defaultCommand(string $commandName): self
    {
        $command = $this->retrieveCommand($commandName);

        if (null === $command) {
            throw new InvalidArgumentException(t('Command "%s" does not exist', [$commandName]));
        }

        parent::defaultCommand($commandName);

        return $this;
    }

    /**
     * Define the callable to perform exit
     */
    public function onExit(callable $fn): self
    {
        $this->onExit = $fn;

        return $this;
    }

    /**
     * Retrieve a registered command by name or alias.
     *
     * @param string $name Command name or alias
     *
     * @return array{action: callable, name: string, alias: string}|null Command data if found, null otherwise
     */
    private function retrieveCommand(string $name): ?array
    {
        if ($name === '') {
            return null;
        }

        if (array_key_exists($name, $this->commandCached)) {
            return $this->commandCached[$name];
        }

        foreach ($this->_commands as $classname => $command) {
            if (in_array($name, [$classname, $command['name'], $command['alias']], true)) {
                return $this->commandCached[$name] = $command;
            }
        }

        return $this->commandCached[$name] = null;
    }

    /**
     * Generate a unique cache key for a command execution.
     *
     * @param string               $command   Command name or FQCN
     * @param array<string, mixed> $arguments Command arguments
     * @param array<string, mixed> $options   Command options
     *
     * @return string Unique cache key
     */
    private function generateCacheKey(string $command, array $arguments = [], array $options = []): string
    {
        ksort($arguments);
        ksort($options);

        return md5(serialize([
            'command'   => $command,
            'arguments' => $arguments,
            'options'   => $options,
        ]));
    }

    /**
     * Converts a CamelCase name to kebab-case.
     *
     * When an option or argument "--my-option" is defined,
     * Ahc/cli returns it in camelcase (myOption),
     * but we want it in kebabcase so we can use $this->option("my-option").
     */
    protected function kebabize(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $input));
    }
}
