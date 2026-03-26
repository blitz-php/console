<?php

/**
 * This file is part of Dimtrovich - Console.
 *
 * (c) 2026 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use Dimtrovich\Console\Command;
use Dimtrovich\Console\Console;
use Dimtrovich\Console\Exceptions\InvalidCommandException;
use Tests\Fixtures\InvalidCommand;
use Tests\Helpers\ConsoleOutput;

use function Ahc\Cli\t;
use function Kahlan\expect;

describe('Console', function () {
    beforeAll(function (): void {
        ConsoleOutput::setUpBeforeClass();
    });

    afterAll(function (): void {
        ConsoleOutput::tearDownAfterClass();
    });

    afterEach(function (): void {
        ConsoleOutput::tearDown();
    });

    beforeEach(function () {
        ConsoleOutput::setUp();
        $this->console = new Console('Test Console', '1.0.0');
    });

    describe('command registration', function () {
        context('with valid command', function () {
            it('adds a command and makes it available', function () {
                $command = new class () extends Command {
                    protected string $name = 'test:command';

                    public function handle()
                    {
                        return 0;
                    }
                };

                $this->console->addCommand($command::class);

                expect($this->console->commandExists('test:command'))->toBe(true);
            });

            it('registers command with alias', function () {
                $command = new class () extends Command {
                    protected string $name  = 'test:alias';
                    protected string $alias = 't:a';

                    public function handle()
                    {
                        return 0;
                    }
                };

                $this->console->addCommand($command::class);

                expect($this->console->commandExists('t:a'))->toBe(true);
            });
        });

        context('with invalid command', function () {
            it('throws InvalidCommandException', function () {
                expect(function () {
                    $this->console->addCommand(InvalidCommand::class);
                })->toThrow(new InvalidCommandException(InvalidCommand::class));
            });

            it('throws exception for non-Command classes', function () {
                $invalidClass = new class () {};

                expect(function () use ($invalidClass) {
                    $this->console->addCommand($invalidClass::class);
                })->toThrow(new InvalidCommandException($invalidClass::class));
            });
        });
    });

    describe('command retrieval', function () {
        beforeEach(function () {
            $this->testCommand = new class () extends Command {
                protected string $name  = 'retrieve:test';
                protected string $alias = 'rt';

                public function handle()
                {
                    return 0;
                }
            };
            $this->console->addCommand(get_class($this->testCommand));
        });

        it('retrieves command by name', function () {
            expect($this->console->commandExists('retrieve:test'))->toBe(true);
        });

        it('retrieves command by alias', function () {
            expect($this->console->commandExists('rt'))->toBe(true);
        });

        it('retrieves command by FQCN', function () {
            $fqcn = get_class($this->testCommand);
            expect($this->console->commandExists($fqcn))->toBe(true);
        });

        it('returns false for non-existent command', function () {
            expect($this->console->commandExists('non:existent'))->toBe(false);
        });

        it('caches retrieved commands', function () {
            // first call - should put it in cache
            $this->console->commandExists('retrieve:test');

            // Check if the cache it's using
            $reflection    = new ReflectionClass($this->console);
            $cacheProperty = $reflection->getProperty('commandCached');
            $cacheProperty->setAccessible(true);
            $cache = $cacheProperty->getValue($this->console);

            expect($cache)->toContainKey('retrieve:test');
        });
    });

    describe('command execution', function () {
        beforeEach(function () {
            $this->command = new class () extends Command {
                protected string $name     = 'exec:test';
                protected array $arguments = ['name' => ['Person to greet']];

                public function handle()
                {
                    $name = $this->argument('name', 'World');
                    $this->writer->write("Hello, {$name}!");

                    return 42;
                }
            };
            $this->console->addCommand(get_class($this->command));
        });

        it('executes command with arguments', function () {
            $result = $this->console->call('exec:test', ['name' => 'Kahlan']);
            $output = ConsoleOutput::buffer();

            expect($output)->toContain('Hello, Kahlan!');
            expect($result)->toBe(42);
        });

        xit('executes command silently', function () {
            $result = $this->console->callSilent('exec:test', ['name' => 'Silent']);
            $output = ConsoleOutput::buffer();

            expect($output)->toBe('');
            expect($result)->toBe(42);
        });

        xit('caches command output', function () {
            $output1 = $this->console->captureOutput('exec:test', ['name' => 'Cache']);
            $output2 = $this->console->captureOutput('exec:test', ['name' => 'Cache']);

            expect($output1)->toBe($output2);
            expect($output1)->toContain('Hello, Cache!');
        });

        xit('generates different cache keys for different arguments', function () {
            $output1 = $this->console->captureOutput('exec:test', ['name' => 'John']);
            $output2 = $this->console->captureOutput('exec:test', ['name' => 'Jane']);

            expect($output1)->not->toBe($output2);
        });

        it('throws CommandNotFoundException for non-existent command', function () {
            $this->console->onExit(fn ($exitCode) => $exitCode);

            $result = $this->console->call('non-existent');
            $output = ConsoleOutput::buffer();

            expect($output)->toContain(t('Command %s not found', ['non-existent']));
            expect($result)->toBe(127);
        });
    });

    describe('hooks and flags', function () {
        it('executes before hook', function () {
            $executed = false;
            $this->console->setHook('before', function () use (&$executed) {
                $executed = true;
            });

            $command = new class () extends Command {
                protected string $name = 'hook:before';

                public function handle()
                {
                    return 0;
                }
            };
            $this->console->addCommand($command::class);
            $this->console->call('hook:before');

            expect($executed)->toBe(true);
        });

        it('executes after hook', function () {
            $executed = false;
            $this->console->setHook('after', function () use (&$executed) {
                $executed = true;
            });

            $command = new class () extends Command {
                protected string $name = 'hook:after';

                public function handle()
                {
                    return 0;
                }
            };
            $this->console->addCommand($command::class);
            $this->console->call('hook:after');

            expect($executed)->toBe(true);
        });

        it('sets debug flag', function () {
            $this->console->setFlag('debug', true);

            $reflection = new ReflectionClass($this->console);
            $flags      = $reflection->getProperty('flags');
            $flags->setAccessible(true);
            $value = $flags->getValue($this->console);

            expect($value['debug'])->toBe(true);
        });
    });
});
