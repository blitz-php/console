<?php

/**
 * This file is part of Dimtrovich - Console.
 *
 * (c) 2026 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Dimtrovich\Console;

use Ahc\Cli\Output\Color;
use BlitzPHP\Contracts\Container\ContainerInterface;
use Dimtrovich\Console\Components\Alert;
use Dimtrovich\Console\Components\Badge;
use Dimtrovich\Console\Components\Logger;
use Dimtrovich\Console\Exceptions\InvalidCommandException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

use function Ahc\Cli\t;

/**
 * Application builder for console applications.
 *
 * This class provides a fluent interface for configuring and running
 * a console application based on the adhocore/cli library.
 *
 * @phpstan-consistent-constructor
 *
 * @example
 * ```php
 * $app = Application::create('MyApp', '1.0.0')
 *     ->withLocale('fr')
 *     ->withDebug()
 *     ->withCommands([
 *         MakeCommand::class,
 *         ServeCommand::class,
 *     ])
 *     ->run();
 * ```
 */
class Application
{
    /**
     * Available built-in themes with descriptions.
     *
     * @var array<string, string>
     */
    public const AVAILABLE_THEMES = [
        'default'   => 'Default theme - matches original adhocore/cli styling',
        'light'     => 'Light theme - optimized for light terminal backgrounds',
        'dark'      => 'Dark theme - optimized for dark terminal backgrounds',
        'solarized' => 'Solarized theme - Ethan Schoonover\'s popular color scheme',
        'monokai'   => 'Monokai theme - vibrant syntax highlighting theme',
        'nord'      => 'Nord theme - arctic, north-bluish color palette',
        'dracula'   => 'Dracula theme - dark theme with vibrant colors',
        'github'    => 'GitHub theme - familiar GitHub interface colors',
    ];

    /**
     * The underlying console application instance.
     */
    private Console $app;

    /**
     * Create a new application builder instance.
     *
     * This constructor is protected to enforce the use of the static factory method.
     *
     * @param string $name    The application name
     * @param string $version The application version
     */
    protected function __construct(string $name, string $version)
    {
        $this->app = new Console($name, $version);

        $this->withTheme('default');
    }

    /**
     * Create a new application instance.
     *
     * This is the main entry point for creating a console application.
     *
     * @param string $name    The application name
     * @param string $version The application version (default: '1.0.0')
     *
     * @return static The application builder instance
     *
     * @example
     * ```php
     * $app = Application::create('My CLI Tool', '2.1.0');
     * ```
     */
    public static function create(string $name, string $version = '1.0.0'): static
    {
        return new static($name, $version);
    }

    /**
     * Run the console application.
     *
     * This method parses the command line arguments, executes the appropriate command,
     * and returns the exit code.
     *
     * @return mixed The application exit code
     *
     * @example
     * ```php
     * $exitCode = Application::create('MyApp')->run();
     * exit($exitCode);
     * ```
     */
    public function run(array $argv = []): mixed
    {
        $argv = $argv !== [] ? $argv : $_SERVER['argv'];

        if (in_array('--debug', $argv, true)) {
            $this->withDebug();
        }

        Color::$enabled = ! in_array('--no-colors', $argv, true);

        $argv = array_filter($argv, fn ($arg) => ! in_array($arg, ['--debug', '--no-colors'], true));

        return $this->app->handle($argv);
    }

    /**
     * Get the console
     */
    public function getConsole(): Console
    {
        return $this->app;
    }

    /**
     * Set the application locale.
     *
     * Loads built-in translations for the specified locale if available.
     *
     * @param string $locale The locale code (e.g., 'fr', 'en', 'es')
     *
     * @return self The current instance
     *
     * @example
     * ```php
     * $app->withLocale('fr'); // Use French translations
     * ```
     */
    public function withLocale(string $locale): self
    {
        if (file_exists($path = __DIR__ . "/../assets/locales/{$locale}.php")) {
            $translations = require $path;

            $this->withTranslations($locale, $translations, true);
        }

        return $this;
    }

    /**
     * Add custom translations for the application.
     *
     * This method allows you to provide custom translation strings or override
     * the built-in translations for a specific locale.
     *
     * @param string $locale       The locale code (e.g., 'fr', 'en', 'es')
     * @param array  $translations Array of translation key-value pairs
     * @param bool   $default      Whether to set this locale as the default
     *
     * @return self The current instance
     *
     * @example
     * ```php
     * $app->withTranslations('fr', [
     *     'Hello %s' => 'Bonjour %s',
     *     'Goodbye'  => 'Au revoir',
     * ], true);
     * ```
     */
    public function withTranslations(string $locale, array $translations, bool $default = false): self
    {
        $this->app->addLocale($locale, $translations, $default);

        return $this;
    }

    /**
     * Set the application logo.
     *
     * The logo will be displayed in the help screen and can be a multi-line ASCII art string.
     *
     * @param string $logo The ASCII art logo
     *
     * @return self The current instance
     *
     * @example
     * ```php
     * $app->withLogo("
     *   _____ _ _ _        _____ _    _ _____
     *  |  ___) (_) |      / ____| |  | |  __ \
     *  | |__ | ||_ _| | | |    | |__| | |__) |
     *  |  __)| || | | | | |    |  __  |  ___/
     *  | |___| || | |_| | |____| |  | | |
     *  |_____|_|/ \___/ \_____|_|  |_|_|
     * ");
     * ```
     */
    public function withLogo(string $logo): self
    {
        $this->app->logo($logo);

        return $this;
    }

    /**
     * Configure default icons behavior for alert and badge components.
     *
     * This method allows you to globally enable or disable default icons
     * for alerts and badges. When enabled, each alert/badge type will
     * display its associated default icon (e.g., ℹ for info, ✓ for success).
     *
     * Individual calls can override this global setting by explicitly
     * passing an icon or using `false` to disable icons for that specific call.
     *
     * @param bool|null $alert  Whether to show default icons for alerts:
     *                          - `true`: Show default icons for all alerts
     *                          - `false`: Hide default icons for all alerts
     *                          - `null`: Keep current setting (no change)
     * @param bool|null $badge  Whether to show default icons for badges:
     *                          - `true`: Show default icons for all badges
     *                          - `false`: Hide default icons for all badges
     *                          - `null`: Keep current setting (no change)
     * @param bool|null $logger Whether to show default icons for log:
     *                          - `true`: Show default icons for all logs
     *                          - `false`: Hide default icons for all logs
     *                          - `null`: Keep current setting (no change)
     *
     * @return self The current instance for method chaining
     *
     * @example
     * ```php
     * // Disable default icons for both alerts and badges
     * $app->withIcons(false, false);
     *
     * // Enable only for alerts, keep badges as is
     * $app->withIcons(true, null);
     *
     * // Disable alerts, enable badges
     * $app->withIcons(false, true);
     *
     * // Later, individual calls can still specify icons
     * $alert->success('Done', 'SUCCESS', Icon::STAR); // Force star icon
     * $badge->info('Message', 'INFO', false);        // No icon for this badge
     * ```
     *
     * @see \Dimtrovich\Console\Components\Alert::showDefaultIcons()
     * @see \Dimtrovich\Console\Components\Badge::showDefaultIcons()
     * @see \Dimtrovich\Console\Components\Logger::showDefaultIcons()
     * @see \Dimtrovich\Console\Icon Available icon constants
     */
    public function withIcons(?bool $alert = null, ?bool $badge = null, ?bool $logger = null): self
    {
        if ($alert !== null) {
            Alert::showDefaultIcons($alert);
        }
        if ($badge !== null) {
            Badge::showDefaultIcons($badge);
        }
        if ($logger !== null) {
            Logger::showDefaultIcons($logger);
        }

        return $this;
    }

    /**
     * Load a built-in or custom theme.
     *
     * This method loads a theme file from the themes directory and applies
     * all its color styles, including both built-in adhocore/cli styles
     * and BlitzPHP custom styles.
     *
     * @param string $theme The theme name (default, light, dark, solarized, monokai, nord, dracula, github)
     *
     * @return self The current instance
     *
     * @throws InvalidArgumentException If the theme file does not exist
     *
     * @example
     * ```php
     * $app->withTheme('dark');      // Use dark theme
     * $app->withTheme('solarized'); // Use solarized theme
     * $app->withTheme('custom');    // Load custom theme from themes/custom/custom.php
     * ```
     */
    public function withTheme(string $theme): self
    {
        if (! file_exists($path = __DIR__ . "/../assets/themes/{$theme}.php")) {
            throw new InvalidArgumentException(
                t(
                    'Theme "%1$s" not found. Available themes: %2$s.',
                    [$theme, implode(', ', array_keys(self::AVAILABLE_THEMES))],
                ),
            );
        }

        $styles = require $path;

        return $this->withStyles($styles);
    }

    /**
     * Apply custom color styles directly.
     *
     * This method allows you to define custom color styles programmatically
     * without creating a theme file. Styles are applied immediately and
     * will override any previously defined styles with the same name.
     *
     * @param array<string, array{fg?: int|string, bg?: int|string, bold?: int}> $styles
     *                                                                                   Associative array where keys are style names and values are style definitions
     *
     * @return self The current instance
     *
     * @example
     * ```php
     * $app->withStyles([
     *     'help_header' => ['fg' => Color::GREEN, 'bold' => 1],
     *     'error'       => ['fg' => Color::RED, 'bg' => 'black'],
     *     'custom_blue' => ['fg' => Color::fg256(69)],
     * ]);
     * ```
     */
    public function withStyles(array $styles): self
    {
        foreach ($styles as $name => $style) {
            Color::style($name, $style);
        }

        return $this;
    }

    /**
     * Set a header title for the help screen.
     *
     * This title will be displayed at the top of the help screen instead of
     * the default "{app name}, version {version}".
     *
     * @param string $headtitle The header title
     *
     * @return self The current instance
     *
     * @example
     * ```php
     * $app->withHeadTitle('My Awesome CLI Tool v2');
     * ```
     */
    public function withHeadTitle(string $headtitle): self
    {
        $this->app->headtitle($headtitle)
            ->setFlag('header', true);

        return $this;
    }

    /**
     * Disable the header title in the help screen.
     *
     * This completely removes the header from the help display.
     *
     * @return self The current instance
     *
     * @example
     * ```php
     * $app->withoutHeadTitle(); // No header displayed
     * ```
     */
    public function withoutHeadTitle(): self
    {
        $this->app->setFlag('header', false);

        return $this;
    }

    /**
     * Enable the footer in the help screen.
     *
     * The footer displays a message reminding users to use `--help` for specific command help.
     *
     * @return self The current instance
     *
     * @example
     * ```php
     * $app->withFooter(); // Show "Run `<command> --help` for specific help"
     * ```
     */
    public function withFooter(): self
    {
        $this->app->setFlag('footer', true);

        return $this;
    }

    /**
     * Set a custom exception handler.
     *
     * This handler will be called when an uncaught exception occurs during command execution.
     *
     * @param callable $handler The exception handler function
     *
     * @return self The current instance
     *
     * @example
     * ```php
     * $app->withExceptionHandle(function(Throwable $e, int $exitCode) {
     *     $this->error('Oops: ' . $e->getMessage());
     *     exit($exitCode);
     * });
     * ```
     */
    public function withExceptionHandle(callable $handler): self
    {
        $this->app->onException($handler);

        return $this;
    }

    /**
     * Enable or disable debug mode.
     *
     * When debug mode is enabled, detailed error information including stack traces
     * will be displayed when exceptions occur.
     *
     * @param bool $debug Whether to enable debug mode (default: true)
     *
     * @return self The current instance
     *
     * @example
     * ```php
     * $app->withDebug();              // Enable debug mode
     * $app->withDebug(false);         // Disable debug mode
     * ```
     */
    public function withDebug($debug = true): self
    {
        $this->app->setFlag('debug', $debug);

        return $this;
    }

    /**
     * Set hooks to be executed before and/or after command execution.
     *
     * @param callable|null $before Callback executed before command execution
     * @param callable|null $after  Callback executed after command execution
     *
     * @return self The current instance
     *
     * @example
     * ```php
     * $app->withHooks(
     *     function(bool $suppress, Command $command) {
     *         $this->comment('Starting: ' . $command->name());
     *     },
     *     function(bool $suppress, Command $command) {
     *         $this->comment('Finished: ' . $command->name());
     *     }
     * );
     * ```
     */
    public function withHooks(?callable $before = null, ?callable $after = null): self
    {
        if ($before) {
            $this->app->setHook('before', $before);
        }

        if ($after) {
            $this->app->setHook('after', $after);
        }

        return $this;
    }

    /**
     * Set the dependency injection container.
     *
     * When a container is provided, commands will be resolved through the container,
     * allowing for dependency injection in command constructors.
     *
     * @param ContainerInterface $container The container instance
     *
     * @return self The current instance
     *
     * @example
     * ```php
     * $app->withContainer($this->container);
     * ```
     */
    public function withContainer(ContainerInterface $container): self
    {
        $this->app->setContainer($container);

        return $this;
    }

    /**
     * Set the PSR logger for the application.
     *
     * This method configures a PSR-3 compatible logger that will be available
     * in all commands via `$this->log()`. Each log message will be both:
     * - Displayed in the console with appropriate styling and icons
     * - Sent to the PSR logger with an optional prefix
     *
     * @param LoggerInterface $logger The PSR logger instance
     * @param string          $prefix Optional prefix to add to all log messages
     *                                (e.g., 'APP' will produce '[APP] Message')
     *
     * @return self The current instance for method chaining
     *
     * @example
     * ```php
     * use Monolog\Logger;
     * use Monolog\Handler\StreamHandler;
     *
     * $logger = new Logger('console');
     * $logger->pushHandler(new StreamHandler('logs/console.log'));
     *
     * // Tous les logs seront préfixés par [APP]
     * $app = Application::create('MyApp')
     *     ->withLogger($logger, 'APP')
     *     ->withCommands([...])
     *     ->run();
     * ```
     *
     * @see \Psr\Log\LoggerInterface
     * @see \Dimtrovich\Console\Components\Logger
     */
    public function withLogger(LoggerInterface $logger, string $prefix = ''): self
    {
        $this->app->setLogger($logger, $prefix);

        return $this;
    }

    /**
     * Add multiple commands to the console application.
     *
     * This method registers one or more command classes with the application.
     * Each command class must extend the `Command` base class.
     *
     * @param list<class-string<Command>> $commands Array of command fully qualified class names
     *
     * @return self The current instance
     *
     * @throws InvalidCommandException If any command class is invalid
     *
     * @example
     * ```php
     * $app->withCommands([
     *     MakeControllerCommand::class,
     *     MakeModelCommand::class,
     *     ServeCommand::class,
     *     RouteListCommand::class,
     * ]);
     * ```
     */
    public function withCommands(array $commands): self
    {
        foreach ($commands as $command) {
            $this->app->addCommand($command);
        }

        return $this;
    }

    /**
     * Add multiple instances of command to the console application.
     *
     * This method registers one or more command classes with the application.
     * Each command class must extend the `Command` base class.
     *
     * @param list<Command> $commands Array of command instances
     *
     * @return self The current instance
     *
     * @throws InvalidCommandException If any command class is invalid
     *
     * @example
     * ```php
     * $app->withCommandInstances([
     *     new MakeControllerCommand(),
     *     new MakeModelCommand($db),
     *     new ServeCommand($dependencies),
     * ]);
     * ```
     */
    public function withCommandInstances(array $commands): self
    {
        foreach ($commands as $command) {
            $this->app->addCommandInstance($command);
        }

        return $this;
    }

    /**
     * Set the default command to execute when no command is specified.
     *
     * This method allows you to define a default command that will be executed
     * when the user runs the application without specifying a command.
     *
     * @param string $command The name of the default command
     *
     * @return self The current instance
     *
     * @example
     * ```php
     * $app->withDefaultCommand('help'); // Show help when no command is specified
     * ```
     */
    public function withDefaultCommand(string $command): self
    {
        $this->app->defaultCommand($command);

        return $this;
    }
}
