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

use function Kahlan\expect;

describe('Command', function () {
    beforeEach(function () {
        $this->console = new Console('Test', '1.0.0');
    });

    describe('initialization', function () {
        it('initializes with console instance', function () {
            $command = new class () extends Command {
                protected string $name = 'test:init';

                public function handle()
                {
                    return 0;
                }
            };

            $baseCommand = $command->initialize($this->console);

            expect($baseCommand)->toBeAnInstanceOf(Dimtrovich\Console\Overrides\Command::class);
        });

        it('sets required properties', function () {
            $command = new class () extends Command {
                protected string $name        = 'test:props';
                protected string $group       = 'testing';
                protected string $description = 'Test description';
                protected string $version     = '1.2.3';
                protected string $alias       = 't:p';

                public function handle()
                {
                    return 0;
                }
            };

            $baseCommand = $command->initialize($this->console);

            expect($baseCommand)->toBeAnInstanceOf(Dimtrovich\Console\Overrides\Command::class);
            expect($baseCommand->name())->toBe('test:props');
            expect($baseCommand->desc())->toBe('Test description');
            expect($baseCommand->group())->toBe('testing');
            expect($baseCommand->alias())->toBe('t:p');
        });
    });

    describe('argument and option handling', function () {
        it('defines arguments correctly', function () {
            $command = new class () extends Command {
                protected string $name     = 'test:args';
                protected array $arguments = [
                    'name' => ['The name of the user'],
                    'age'  => ['The age of the user', 25],
                ];

                public function handle()
                {
                    return $this->arguments();
                }
            };

            $baseCommand = $command->initialize($this->console);

            expect($baseCommand->args())->toBeAn('array');
        });

        it('defines options correctly', function () {
            $command = new class () extends Command {
                protected string $name   = 'test:opts';
                protected array $options = [
                    'force' => ['Force the operation', false],
                    'env'   => ['The environment', 'prod', 'strval'],
                ];

                public function handle()
                {
                    return $this->options();
                }
            };

            $baseCommand = $command->initialize($this->console);

            expect($baseCommand->allOptions())->toBeAn('array');
        });

        it('retrieves argument values', function () {
            $command = new class () extends Command {
                protected string $name     = 'test:get';
                protected array $arguments = ['id' => ['User ID']];

                public function handle()
                {
                    return $this->argument('id');
                }
            };

            $command->initialize($this->console);
            $command->setParameters(['id' => 123], []);

            expect($command->argument('id'))->toBe(123);
        });

        it('returns default for missing argument', function () {
            $command = new class () extends Command {
                protected string $name = 'test:default';

                public function handle()
                {
                    return $this->argument('missing', 'default');
                }
            };

            $command->initialize($this->console);
            $command->setParameters([], []);

            expect($command->argument('missing', 'default'))->toBe('default');
        });

        it('checks if argument exists', function () {
            $command = new class () extends Command {
                protected string $name     = 'test:has';
                protected array $arguments = ['id' => ['User ID']];

                public function handle()
                {
                    return [
                        'has_id'   => $this->hasArgument('id'),
                        'has_name' => $this->hasArgument('name'),
                    ];
                }
            };

            $command->initialize($this->console);
            $command->setParameters(['id' => 123], []);

            expect($command->hasArgument('id'))->toBe(true);
            expect($command->hasArgument('name'))->toBe(false);
        });

        it('retrieves option values', function () {
            $command = new class () extends Command {
                protected string $name   = 'test:opt';
                protected array $options = [
                    'force' => ['Force operation', false],
                ];

                public function handle()
                {
                    return $this->option('force');
                }
            };

            $command->initialize($this->console);
            $command->setParameters([], ['force' => true]);

            expect($command->option('force'))->toBe(true);
        });
    });

    describe('command calling', function () {
        beforeEach(function () {
            $this->command2 = new class () extends Command {
                protected string $name = 'second';

                public function handle()
                {
                    $this->writer->write('Second executed');

                    return 99;
                }
            };
            $this->console->addCommand(get_class($this->command2));
        });

        it('calls another command', function () {
            $command1 = new class () extends Command {
                protected string $name = 'first';

                public function handle()
                {
                    return $this->call('second');
                }
            };

            $this->console->addCommand($command1::class);
            $result = $this->console->call('first');

            expect($result)->toBe(99);
        });

        it('calls another command with arguments', function () {
            $command1 = new class () extends Command {
                protected string $name = 'first';

                public function handle()
                {
                    return $this->call('second', ['arg' => 'value']);
                }
            };

            $this->console->addCommand($command1::class);

            // Pas d'exception = succès
            expect(function () {
                $this->console->call('first');
            })->not->toThrow();
        });

        it('checks command existence', function () {
            $command = new class () extends Command {
                protected string $name = 'checker';

                public function handle()
                {
                    return $this->commandExists('second');
                }
            };

            $this->console->addCommand($command::class);
            $result = $this->console->call('checker');

            expect($result)->toBe(true);
        });
    });

    describe('magic methods', function () {
        it('accesses name via __call', function () {
            $command = new class () extends Command {
                protected string $name  = 'magic:name';
                protected string $alias = 'mn';

                public function handle()
                {
                    return 0;
                }
            };

            expect($command->name())->toBe('magic:name');
            expect($command->alias())->toBe('mn');
        });

        it('throws exception for undefined method', function () {
            $command = new class () extends Command {
                protected string $name = 'test';

                public function handle()
                {
                    return 0;
                }
            };

            expect(function () use ($command) {
                $command->undefined();
            })->toThrow(new InvalidArgumentException('Undefined method "undefined" called.'));
        });
    });

    describe('pad method', function () {
        it('pads a string with spaces', function () {
            $command = new class () extends Command {
                protected string $name = 'test';

                public function handle()
                {
                    return 0;
                }
            };

            expect($command->pad('test', 10))->toBe('test        ');
            expect($command->pad('test', 10, 0, 2))->toBe('  test      ');
            expect($command->pad('test', 5, 1, 1))->toBe(' test  ');
        });
    });
});
