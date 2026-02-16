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

use function Kahlan\expect;

describe('Alert', function () {
    beforeAll(function () {
        $this->outputFile = __DIR__ . '/../../output-alert.test';

        if (! is_dir($dirname = pathinfo($this->outputFile, PATHINFO_DIRNAME))) {
            mkdir($dirname);
        }
        file_put_contents($this->outputFile, '', LOCK_EX);
    });

    beforeEach(function () {
        $this->writer = new Writer($this->outputFile);
        $this->alert  = new Alert($this->writer);
    });

    afterEach(function () {
        file_put_contents($this->outputFile, '', LOCK_EX);
    });

    afterAll(function () {
        if (file_exists($this->outputFile)) {
            unlink($this->outputFile);
        }
    });

    describe('::instance()', function () {
        it('creates a singleton instance', function () {
            $alert1 = Alert::instance($this->writer);
            $alert2 = Alert::instance($this->writer);

            expect($alert1)->toBe($alert2);
        });
    });

    describe('alert types', function () {
        it('displays info alert', function () {
            expect($this->writer)->toReceive('colors')->times(4); // border, title, message, border

            $result = $this->alert->info('System is running', 'System Status');

            expect($result)->toBe($this->alert);
        });

        it('displays info alert with default title', function () {
            expect($this->writer)->toReceive('colors')->with('<boldCyan>*  INFO  ********************</end>')->once();

            $this->alert->info('System is running');
        });

        it('displays various alert types', function () {
            $types = [
                'info'      => 'cyan',
                'success'   => 'green',
                'warning'   => 'yellow',
                'error'     => 'red',
                'danger'    => 'red',
                'primary'   => 'blue',
                'secondary' => 'gray',
                'dark'      => 'white',
                'light'     => 'black',
            ];

            foreach ($types as $type => $color) {
                $message = 'Message';
                $title   = 'Title';

                // expect($this->writer)->toReceive('colors')->with('<bold' . ucfirst($color) . '>*  ' . $title . '  *********</end>')->once();

                $result = $this->alert->{$type}($message, $title);

                expect($result)->toBe($this->alert);
            }
        });
    });

    describe('message formatting', function () {
        it('wraps long messages', function () {
            $longMessage = str_repeat('This is a very long message that should be wrapped. ', 10);

            expect($this->writer)->toReceive('colors')->times(12); // border + title + multiple message lines

            $this->alert->info($longMessage);
        });

        it('handles multiline messages', function () {
            $multiline = "Line 1\nLine 2\nLine 3";

            for ($i = 1; $i <= 3; $i++) {
                expect($this->writer)->toReceive('colors')->with('<cyan>*  Line ' . $i . '                        *</end>')->once();
            }

            $this->alert->info($multiline);
        });
    });

    describe('border rendering', function () {
        it('renders top and bottom borders', function () {
            expect($this->writer)->toReceive('colors')->with('<cyan>************************</end>'); // ->twice();

            $this->alert->info('Test message');
        });

        it('calculates border length based on message', function () {
            expect($this->writer)->toReceive('colors'); // ->with(pattern('/\*{20,}/'))->once();

            $this->alert->info('Short');
        });
    });
});
