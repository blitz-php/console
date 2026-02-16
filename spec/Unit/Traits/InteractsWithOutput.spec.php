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
use Dimtrovich\Console\Components\Alert;
use Dimtrovich\Console\Components\Badge;
use Dimtrovich\Console\Components\Logger;
use Dimtrovich\Console\Components\ProgressBar;
use Dimtrovich\Console\Overrides\Cursor;
use Dimtrovich\Console\Traits\InteractsWithOutput;
use Kahlan\Arg;
use Kahlan\Plugin\Double;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

use function Kahlan\expect;

describe('Traits / InteractsWithOutput', function () {
    fileHook(
        file: 'output-io-output.test',
        beforeAll: function () {
            $this->getOutputer = function ($writer) {
                $inputer = new class () {
                    use InteractsWithOutput;

                    protected $writer;
                    protected $terminal;
                    protected $cursor;
                    protected $color;

                    public function setParams($writer)
                    {
                        $this->writer   = $writer;
                        $this->terminal = $writer->terminal();
                        $this->color    = $this->writer->colorizer();
                        $this->cursor   = new Cursor();

                        return $this;
                    }
                };

                return $inputer->setParams($writer);
            };
        },
        beforeEach: function ($files) {
            $this->writer = new Writer($files[0]);
            $this->output = $this->getOutputer($this->writer);
        },
    );

    describe('components', function () {
        it('returns Alert instance', function () {
            expect(Alert::instance($this->writer))->toBeAnInstanceOf(Alert::class);

            $this->output->alert();
        });

        it('returns Badge instance', function () {
            expect(Badge::instance($this->writer))->toBeAnInstanceOf(Badge::class);

            $this->output->badge();
        });
    });

    describe('task display', function () {
        it('displays task', function () {
            expect($this->writer)->toReceive('write')->with('>> Processing...', true)->once();

            $result = $this->output->task('Processing...');

            expect($result)->toBe($this->output);
        });

        it('displays task with sleep', function () {
            expect($this->writer)->toReceive('write')->with('>> Sleeping...', true)->once();

            $start = microtime(true);
            $this->output->task('Sleeping...', 1);
            $elapsed = microtime(true) - $start;

            expect($elapsed >= 1)->toBe(true);
        });
    });

    describe('table', function () {
        it('displays table with old signature (2D array)', function () {
            $rows = [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
            ];

            expect($this->writer)->toReceive('table')->with($rows, [])->once();

            $this->output->table($rows);
        });

        it('displays table with new signature (headers + rows)', function () {
            $headers = ['Name', 'Age'];
            $rows    = [
                ['John', 30],
                ['Jane', 25],
            ];

            $expectedRows = [
                ['Name' => 'John', 'Age' => 30],
                ['Name' => 'Jane', 'Age' => 25],
            ];

            expect($this->writer)->toReceive('table')->with($expectedRows, [])->once();

            $this->output->table($headers, $rows);
        });

        it('displays table with styles', function () {
            $headers = ['ID', 'Name'];
            $rows    = [[1, 'Alice']];
            $styles  = ['border' => '|', 'padding' => 2];

            expect($this->writer)->toReceive('table')->with(Arg::toBeA('array'), $styles)->once();

            $this->output->table($headers, $rows, $styles);
        });
    });

    describe('json', function () {
        it('displays JSON formatted data', function () {
            $data = ['name' => 'John', 'age' => 30];

            expect($this->writer)->toReceive('write')->with(
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            )->once();

            $this->output->json($data);
        });
    });

    describe('progress bar', function () {
        it('creates progress bar instance', function () {
            $progress = $this->output->progress(100);

            expect($progress)->toBeAnInstanceOf(ProgressBar::class);
        });

        it('creates progress bar without total', function () {
            $progress = $this->output->progress();

            expect($progress)->toBeAnInstanceOf(ProgressBar::class);
        });
    });

    describe('clear screen', function () {
        it('clears screen on non-Windows', function () {
            $terminal = $this->writer->terminal();
            allow($terminal)->toReceive('isWindows')->andReturn(false);

            expect($this->writer)->toReceive('raw')->with("\033[H\033[2J")->once();

            $this->output->clearScreen();
        });

        xit('clears screen on Windows with VT100', function () {
            $terminal = $this->writer->terminal();
            allow($terminal)->toReceive('isWindows')->andReturn(true);

            // Mock sapi_windows_vt100_support
            if (! function_exists('sapi_windows_vt100_support')) {
                eval('function sapi_windows_vt100_support($stream) { return true; }');
            }

            expect($this->writer)->toReceive('eol')->with(40)->once();

            $this->output->clearScreen();
        });
    });

    describe('counter', function () {
        it('displays counter with animation', function () {
            expect($this->writer)->toReceive('write')->times(202); // (0-100) 2 times
            expect($this->writer)->toReceive('eol')->once();

            $this->output->counter(0, 100, 1);
        });

        it('displays counter with custom steps', function () {
            expect($this->writer)->toReceive('write')->times(times: 22); // (0,10,20,...,100) 2 times
            expect($this->writer)->toReceive('eol')->once();

            $this->output->counter(0, 100, 10);
        });
    });

    describe('Justify / center / border', function () {
        it('writes border line', function () {
            expect($this->writer)->toReceive('comment')->with(Arg::toContain('---'))->once();

            $this->output->border(10);
        });

        it('writes justified text', function () {
            expect($this->writer)->toReceive('write');

            $this->output->justify('Left', 'Right');
        });

        it('writes centered text', function () {
            expect($this->writer)->toReceive('write');

            $this->output->center('Centered');
        });
    });

    describe('log method', function () {
        beforeEach(function () {
            $this->psrLogger = Double::instance(['implements' => [LoggerInterface::class]]);

            Logger::setLogger($this->psrLogger, 'TEST');
        });

        it('returns Logger instance', function () {
            $logger = $this->output->log();

            expect($logger)->toBeAnInstanceOf(Logger::class);
        });

        it('returns logger with default prefix', function () {
            $logger = $this->output->log();

            expect($logger->prefix())->toBe('TEST');
        });

        it('returns logger with custom prefix', function () {
            $logger = $this->output->log('CUSTOM');

            expect($logger->prefix())->toBe('CUSTOM');
        });

        it('returns new instance for different prefix', function () {
            $logger1 = $this->output->log('DB');
            $logger2 = $this->output->log('CACHE');

            expect($logger1)->not->toBe($logger2);
        });

        it('returns same instance if we use global prefix', function () {
            $logger1 = $this->output->log();
            $logger2 = $this->output->log();

            expect($logger1)->toBe($logger2);
        });

        it('throws exception when no logger configured', function () {
            // Reinitialise
            $reflection = new ReflectionClass(Logger::class);
            $loggerProp = $reflection->getProperty('logger');
            $loggerProp->setAccessible(true);
            $loggerProp->setValue(null, null);

            expect(function () {
                $this->output->log();
            })->toThrow(new RuntimeException('No PSR logger configured. Use $app->withLogger() to set one.'));
        });

        it('logs through the returned instance', function () {
            $this->output->log()->resetInstance();

            allow($this->psrLogger)->toReceive('log')->with(LogLevel::INFO, '[TEST] Message', []);

            expect($this->writer)->toReceive('boldWhiteBgBlue')->once();
            expect($this->writer)->toReceive('write')->with(' Message', true)->once();

            $this->output->log()->info('Message');
        });
    });
});
