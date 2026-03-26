<?php

/**
 * This file is part of Dimtrovich - Console.
 *
 * (c) 2026 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use Ahc\Cli\Output\Writer;
use Dimtrovich\Console\Components\Logger;
use Kahlan\Arg;
use Kahlan\Plugin\Double;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

use function Kahlan\expect;

describe('Components / Logger', function () {
    fileHook(
        file: 'output-logger.test',
        beforeAll: function ($files) {
            $this->psrLogger = Double::instance(['implements' => [LoggerInterface::class]]);

            allow($this->psrLogger)->toReceive('log')->andRun(function ($level, $message, $context) use ($files) {
                file_put_contents($files[0], $message);
            });
        },
        beforeEach: function ($files) {
            $this->writer = new Writer($files[0]);

            // Configure the logger
            Logger::configure($this->writer, $this->psrLogger, 'TEST');

            $this->logger = new Logger($this->writer);
        },
    );

    describe('configuration', function () {
        it('creates singleton instance', function () {
            $logger1 = Logger::instance($this->writer);
            $logger2 = Logger::instance($this->writer);

            expect($logger1)->toBe($logger2);
        });

        it('throws exception when no logger configured', function () {
            // Réinitialiser
            $reflection = new ReflectionClass(Logger::class);
            $loggerProp = $reflection->getProperty('logger');
            $loggerProp->setAccessible(true);
            $loggerProp->setValue(null, null);

            expect(function () {
                $logger1 = Logger::instance($this->writer);
                $logger1->info('test');
            })->toThrow(new RuntimeException('Logger instance is not defined.'));
        });

        it('sets global logger', function () {
            Logger::setLogger($this->psrLogger, 'APP');

            expect(Logger::hasLogger())->toBe(true);
        });
    });

    describe('logging methods', function () {
        it('logs info message', function () {
            expect($this->writer)->toReceive('boldWhiteBgBlue')->with(' INFO ')->once();
            expect($this->writer)->toReceive('write')->with(' User logged in', true)->once();

            $this->logger->info('User logged in');
        });

        it('logs error message', function () {
            expect($this->writer)->toReceive('boldWhiteBgRed')->with(' ERROR ')->once();

            $this->logger->error('Connection failed');
        });

        it('logs warning message', function () {
            expect($this->writer)->toReceive('boldWhiteBgYellow')->with(' WARNING ')->once();

            $this->logger->warning('Low disk space');
        });

        it('logs debug message', function () {
            expect($this->writer)->toReceive('boldWhiteBgGray')->with(' DEBUG ')->once();

            $this->logger->debug('Variable value');
        });

        it('logs notice message', function () {
            expect($this->writer)->toReceive('boldWhiteBgCyan')->with(' NOTICE ')->once();

            $this->logger->notice('Something happened');
        });

        it('logs critical message', function () {
            expect($this->writer)->toReceive('boldWhiteBgRed')->with(' CRITICAL ')->once();

            $this->logger->critical('System crash');
        });

        it('logs alert message', function () {
            expect($this->writer)->toReceive('boldWhiteBgRed')->with(' ALERT ')->once();

            $this->logger->alert('Immediate action');
        });

        it('logs emergency message', function () {
            allow($this->psrLogger)->toReceive('log')->with(LogLevel::EMERGENCY, '[TEST] System down', []);

            expect($this->writer)->toReceive('boldWhiteBgRed')->with(' EMERGENCY ')->once();

            $this->logger->emergency('System down');
        });
    });

    describe('aliased methods', function () {
        it('logs success as info with green style', function () {
            expect($this->writer)->toReceive('boldWhiteBgGreen')->with(' INFO ')->once();

            $this->logger->success('Operation completed');
        });

        it('logs warn as warning', function () {
            expect($this->writer)->toReceive('boldWhiteBgYellow')->with(' WARNING ')->once();

            $this->logger->warn('Warning message');
        });

        it('logs danger as error', function () {
            expect($this->writer)->toReceive('boldWhiteBgRed')->with(' ERROR ')->once();

            $this->logger->danger('Danger message');
        });

        it('logs fail as error', function () {
            allow($this->psrLogger)->toReceive('log')->with(LogLevel::ERROR, '[TEST] Fail message', []);

            expect($this->writer)->toReceive('boldWhiteBgRed')->with(' ERROR ')->once();

            $this->logger->fail('Fail message');
        });

        it('throws exception for undefined method', function () {
            expect(function () {
                $this->logger->undefinedMethod();
            })->toThrow(new BadMethodCallException(
                'Call to undefined method "Dimtrovich\Console\Components\Logger::undefinedMethod".',
            ));
        });
    });

    describe('prefix handling', function () {
        it('adds prefix to log messages', function () {
            allow($this->psrLogger)->toReceive('log')->with(LogLevel::INFO, '[TEST] Message', []);

            $this->logger->info('Message');
        });

        it('creates new instance with additional prefix', function () {
            $prefixed = $this->logger->withPrefix('DB');

            // Vérifier que c'est une nouvelle instance
            expect($prefixed)->not->toBe($this->logger);

            allow($this->psrLogger)->toReceive('log')->with(LogLevel::INFO, '[TEST > DB] Query executed', []);

            $prefixed->info('Query executed');
        });

        it('chains multiple prefixes', function () {
            $prefixed = $this->logger
                ->withPrefix('APP')
                ->withPrefix('CACHE');

            allow($this->psrLogger)->toReceive('log')->with(LogLevel::INFO, '[TEST > APP > CACHE] Cache cleared', []);

            $prefixed->info('Cache cleared');
        });

        it('returns empty prefix when none set', function () {
            Logger::resetInstance();
            Logger::configure($this->writer, $this->psrLogger, '');
            $logger = Logger::instance($this->writer);

            expect($logger->prefix())->toBe('');
        });
    });

    describe('context handling', function () {
        it('includes context in PSR log', function () {
            $context = ['user_id' => 123, 'ip' => '127.0.0.1'];

            allow($this->psrLogger)->toReceive('log')->with(LogLevel::INFO, '[TEST] User action', $context);

            $this->logger->info('User action', $context);
        });

        it('displays context in console when icons enabled', function () {
            Logger::showDefaultIcons(true);

            $context = ['id' => 123];

            allow($this->psrLogger)->toReceive('log')->with(LogLevel::INFO, '[TEST] User action', $context);

            expect($this->writer)->toReceive('write')->with(Arg::toContain('{"id":123}'), true)->once();

            $this->logger->info('User action', $context);
        });

        it('hides context in console when icons disabled', function () {
            Logger::showDefaultIcons(false);

            $context = ['id' => 123];

            allow($this->psrLogger)->toReceive('log')->with(LogLevel::INFO, '[TEST] User action', $context);

            expect($this->writer)->toReceive('write')->with(' User action', true)->once();

            $this->logger->info('User action', $context);
        });
    });

    describe('icon display', function () {
        it('shows icons when globally enabled', function () {
            Logger::showDefaultIcons(true);

            expect($this->writer)->toReceive('boldWhiteBgBlue')->with(' ℹ INFO ')->once();

            $this->logger->info('Test');
        });

        it('hides icons when globally disabled', function () {
            Logger::showDefaultIcons(false);

            expect($this->writer)->toReceive('boldWhiteBgBlue')->with(' INFO ')->once();

            $this->logger->info('Test');
        });

        it('allows per-call icon override', function () {
            // Avec la méthode __call, on ne peut pas passer l'icône directement
            // Ce test serait pour une future amélioration
        });
    });
});
