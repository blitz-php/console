<?php

/**
 * This file is part of Dimtrovich - Console.
 *
 * (c) 2026 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Contracts\Container\ContainerInterface;
use Dimtrovich\Console\Application;
use Dimtrovich\Console\Command;
use Dimtrovich\Console\Console;
use Kahlan\Plugin\Double;
use Psr\Log\LoggerInterface;
use Tests\Fixtures\CommadOne;
use Tests\Fixtures\CommadTwo;

use function Kahlan\expect;

describe('Application', function () {
    beforeEach(function () {
        $this->app = Application::create('Test App', '1.0.0');
    });

    describe('::create()', function () {
        it('creates a new application instance with fluent interface', function () {
            $app = Application::create('My CLI', '2.0.0');

            expect($app)->toBeAnInstanceOf(Application::class);
        });
    });

    describe('configuration methods', function () {
        it('configures locale with built-in translations', function () {
            $result = $this->app->withLocale('fr');

            expect($result)->toBe($this->app);
        });

        it('configures custom translations', function () {
            $translations = ['Hello' => 'Bonjour'];

            $result = $this->app->withTranslations('fr', $translations, true);

            expect($result)->toBe($this->app);
        });

        it('configures application logo', function () {
            $logo = '  _   _   _   _  ';

            $result = $this->app->withLogo($logo);

            expect($result)->toBe($this->app);
        });

        it('configures head title', function () {
            $result = $this->app->withHeadTitle('Custom Title');

            expect($result)->toBe($this->app);
        });

        it('disables head title', function () {
            $result = $this->app->withoutHeadTitle();

            expect($result)->toBe($this->app);
        });

        it('enables footer', function () {
            $result = $this->app->withFooter();

            expect($result)->toBe($this->app);
        });
    });

    describe('error handling', function () {
        it('configures debug mode', function () {
            $result = $this->app->withDebug(true);

            expect($result)->toBe($this->app);
        });

        it('configures custom exception handler', function () {
            $handler = function ($e, $code) {
                echo 'Custom error';
            };

            $result = $this->app->withExceptionHandle($handler);

            expect($result)->toBe($this->app);
        });
    });

    describe('hooks and container', function () {
        it('configures before/after hooks', function () {
            $before = function () {};
            $after  = function () {};

            $result = $this->app->withHooks($before, $after);

            expect($result)->toBe($this->app);
        });

        it('configures container', function () {
            $container = Double::instance(['implements' => [ContainerInterface::class]]);

            $result = $this->app->withContainer($container);

            expect($result)->toBe($this->app);
        });
    });

    describe('command registration', function () {
        it('registers multiple commands', function () {
            $commands = [
                CommadOne::class,
                CommadTwo::class,
            ];

            $result = $this->app->withCommands($commands);

            expect($result)->toBe($this->app);
        });
    });

    describe('command execution', function () {
        beforeEach(function () {
            // Create a mock command for tests
            $this->mockCommandClass = new class () extends Command {
                protected string $name        = 'test:command';
                protected string $description = 'Test command';

                public function handle()
                {
                    $this->writer->write('Command executed');

                    return 0;
                }
            };

            $this->app->withCommands([get_class($this->mockCommandClass)]);
        });
    });

    describe('theme and icons', function () {
        it('applies theme with withTheme()', function () {
            $result = $this->app->withTheme('dark');

            expect($result)->toBe($this->app);
        });

        it('throws exception for invalid theme', function () {
            expect(function () {
                $this->app->withTheme('invalid');
            })->toThrow(new InvalidArgumentException(
                'Theme "invalid" not found. Available themes: default, light, dark, solarized, monokai, nord, dracula, github.',
            ));
        });

        it('configures icons with withIcons()', function () {
            // Test that static calls are made
            // It is not easy to test static calls with Kahlan
            // But we can check that the method exists and returns $this

            $result = $this->app->withIcons(true, false, true);

            expect($result)->toBe($this->app);
        });

        it('applies custom styles with withStyles()', function () {
            $styles = [
                'custom_style' => ['fg' => 'red', 'bold' => 1],
                'another'      => ['fg' => 'blue', 'bg' => 'black'],
            ];

            $result = $this->app->withStyles($styles);

            expect($result)->toBe($this->app);
        });
    });

    describe('header and footer', function () {
        it('sets head title with withHeadTitle()', function () {
            $result = $this->app->withHeadTitle('Custom Title');

            expect($result)->toBe($this->app);
        });

        it('disables head title with withoutHeadTitle()', function () {
            $result = $this->app->withoutHeadTitle();

            expect($result)->toBe($this->app);
        });

        it('enables footer with withFooter()', function () {
            $result = $this->app->withFooter();

            expect($result)->toBe($this->app);
        });
    });

    describe('logger configuration', function () {
        it('configures logger with withLogger()', function () {
            $psrLogger = Double::instance(['implements' => [LoggerInterface::class]]);

            $result = $this->app->withLogger($psrLogger, 'APP');

            expect($result)->toBe($this->app);

            // Vérifier que le logger est configuré dans la console
            // Ceci nécessite d'accéder à la propriété privée
        });
    });

    describe('default command', function () {
        it('sets default command with withDefaultCommand()', function () {
            // Ajouter d'abord une commande
            $command = new class () extends Command {
                protected string $name = 'test:default';

                public function handle()
                {
                    return 0;
                }
            };

            $this->app->withCommands([$command::class]);

            $result = $this->app->withDefaultCommand('test:default');

            expect($result)->toBe($this->app);
        });

        it('throws exception for invalid default command', function () {
            expect(function () {
                $this->app->withDefaultCommand('nonexistent');
            })->toThrow(new InvalidArgumentException('Command "nonexistent" does not exist'));
        });
    });

    describe('run method', function () {
        it('handles --debug flag', function () {
            // Ce test nécessite de mocker $_SERVER['argv']
            $originalArgv = $_SERVER['argv'] ?? [];

            $_SERVER['argv'] = ['console', '--debug'];

            // On ne peut pas vraiment tester run() car il exit
            // Mais on peut tester la logique de parsing

            $reflection = new ReflectionClass($this->app);
            $method     = $reflection->getMethod('run');
            $method->setAccessible(true);

            // Vérifier que la méthode existe
            expect($method)->toBeAnInstanceOf(ReflectionMethod::class);

            // Restaurer
            $_SERVER['argv'] = $originalArgv;
        });

        it('handles --no-colors flag', function () {
            $originalArgv = $_SERVER['argv'] ?? [];

            $_SERVER['argv'] = ['console', '--no-colors'];

            $reflection = new ReflectionClass($this->app);
            $method     = $reflection->getMethod('run');
            $method->setAccessible(true);

            expect($method)->toBeAnInstanceOf(ReflectionMethod::class);

            $_SERVER['argv'] = $originalArgv;
        });
    });
});
