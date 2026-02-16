<?php

/**
 * This file is part of Dimtrovich - Console.
 *
 * (c) 2026 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use Ahc\Cli\Output\Color;
use Ahc\Cli\Output\Writer;
use Dimtrovich\Console\Traits\FormatsOutput;
use Kahlan\Arg;

describe('Traits / FormatsOutput', function () {
    fileHook(
        file: 'output-format.test',
        beforeAll: function () {
            Color::style('underline', ['bold' => 4]);
            Color::style('italic', ['bold' => 3]);
            Color::style('strike', ['bold' => 9]);

            Color::style('magenta', ['fg' => Color::fg256(201)]);
            Color::style('indigo', ['fg' => Color::fg256(54)]);

            $this->getFormatter = function ($writer) {
                $formatter = new class () {
                    use FormatsOutput;

                    protected Writer $writer;

                    public function setWriter($w)
                    {
                        $this->writer = $w;

                        return $this;
                    }

                    public function getWriter()
                    {
                        return $this->writer;
                    }
                };

                return $formatter->setWriter($writer);
            };
        },
        beforeEach: function ($files) {
            $this->writer    = new Writer($files[0]);
            $this->formatter = $this->getFormatter($this->writer);
        },
    );

    describe('basic output', function () {
        it('writes a line with color', function () {
            expect($this->writer)->toReceive('colors')->with('<red>Error message</end>')->once();

            $result = $this->formatter->line('Error message', 'red');

            expect($result)->toBe($this->formatter);
        });

        it('writes a line without color', function () {
            expect($this->writer)->toReceive('write')->with('Plain text')->once();

            $this->formatter->line('Plain text');
        });
    });

    describe('colored messages', function () {
        it('writes info message', function () {
            expect($this->writer)->toReceive('info')->with('Information')->once();
            expect($this->writer)->toReceive('eol')->once();

            $this->formatter->info('Information');
        });

        it('writes success message', function () {
            expect($this->writer)->toReceive('ok')->with('Success')->once();
            expect($this->writer)->toReceive('eol')->once();

            $this->formatter->success('Success');
        });

        it('writes warning message', function () {
            expect($this->writer)->toReceive('warn')->with('Warning')->times(2);
            expect($this->writer)->toReceive('eol')->times(2);

            $this->formatter->warning('Warning');
            $this->formatter->warn('Warning');
        });

        it('writes error message', function () {
            expect($this->writer)->toReceive('error')->with('Error')->once();
            expect($this->writer)->toReceive('eol')->once();

            $this->formatter->error('Error');
        });

        it('writes comment message', function () {
            expect($this->writer)->toReceive('comment')->with('Comment')->once();
            expect($this->writer)->toReceive('eol')->once();

            $this->formatter->comment('Comment');
        });

        it('writes ok message', function () {
            expect($this->writer)->toReceive('ok')->with('Ok')->once();
            expect($this->writer)->toReceive('eol')->once();

            $this->formatter->ok('Ok');
        });

        it('writes question message', function () {
            expect($this->writer)->toReceive('question')->with('Question?')->once();
            expect($this->writer)->toReceive('eol')->once();

            $this->formatter->question('Question?');
        });

        it('writes note message', function () {
            expect($this->writer)->toReceive('comment')->with('NOTE: This is a note')->once();
            expect($this->writer)->toReceive('eol')->once();

            $this->formatter->note('This is a note');
        });

        it('writes notice message', function () {
            expect($this->writer)->toReceive('info')->with('NOTICE: Important')->once();
            expect($this->writer)->toReceive('eol')->once();

            $this->formatter->notice('Important');
        });

        it('writes caution message', function () {
            expect($this->writer)->toReceive('warn')->with('CAUTION: Careful')->once();
            expect($this->writer)->toReceive('eol')->once();

            $this->formatter->caution('Careful');
        });

        it('writes debug message', function () {
            expect($this->writer)->toReceive('comment')->with('DEBUG: Variable value')->once();
            expect($this->writer)->toReceive('eol')->once();

            $this->formatter->debug('Variable value');
        });

        it('writes fail message', function () {
            expect($this->writer)->toReceive('error')->with('FAIL: Task failed')->once();
            expect($this->writer)->toReceive('eol')->once();

            $this->formatter->fail('Task failed');
        });
    });

    describe('color methods', function () {
        it('colorizes text', function () {
            expect($this->writer)->toReceive('colors')->with('<blue>Blue text</end>')->once();

            $this->formatter->colorize('Blue text', 'blue');
        });

        it('colorizes with eol', function () {
            expect($this->writer)->toReceive('colors')->with('<green>Green text</end><eol>')->once();

            $this->formatter->colorize('Green text', 'green', true);
        });

        it('writes colored text', function () {
            $colors = [
                'red'    => 'Red alert',
                'green'  => 'Success',
                'blue'   => 'Info',
                'yellow' => 'Warning',

                'cyan', 'gray', 'black', 'white', 'purple', 'magenta', 'indigo',
            ];

            foreach ($colors as $key => $value) {
                $color   = is_string($key) ? $key : $value;
                $message = ucfirst($value);

                expect($this->writer)->toReceive('colors')->with('<' . $color . '>' . $message . '</end>')->once();

                $this->formatter->{$color}($message);
            }
        });

        it('writes bold text', function () {
            expect($this->writer)->toReceive('bold')->with('Bold text')->once();
            $this->formatter->bold('Bold text');
        });

        it('write formatted text', function () {
            $formats = [
                'italic'    => 'Italic text',
                'underline' => 'Underlined',
                'strike'    => 'Struck',
            ];

            foreach ($formats as $format => $message) {
                expect($this->writer)->toReceive('colors')->with('<' . $format . '>' . $message . '</end>')->once();
                $this->formatter->{$format}($message);
            }

            foreach ($formats as $format => $message) {
                expect($this->writer)->toReceive('colors')->with('<' . $format . '>' . $message . '</end><eol>')->once();
                $this->formatter->{$format}($message, true);
            }
        });
    });

    describe('lists', function () {
        it('displays bullet list', function () {
            expect($this->writer)->toReceive('colors')->with('<yellow>Items:</end>')->once();
            expect($this->writer)->toReceive('write')->with('  • Item 1')->once();
            expect($this->writer)->toReceive('write')->with('  • Item 2')->once();

            $this->formatter->bulletList(['Item 1', 'Item 2'], 'Items:');
        });

        it('displays bullet list without title', function () {
            expect($this->writer)->not->toReceive('colors')->with(Arg::toContain('Title'));
            expect($this->writer)->toReceive('write')->with('  • Item 1')->once();

            $this->formatter->bulletList(['Item 1']);
        });

        it('displays numbered list', function () {
            expect($this->writer)->toReceive('colors')->with('  <green>1.</end> Item 1')->once();
            expect($this->writer)->toReceive('colors')->with('  <green>2.</end> Item 2')->once();

            $this->formatter->numberedList(['Item 1', 'Item 2'], 'Numbers:');
        });
    });

    describe('color methods with eol', function () {
        it('writes red with eol', function () {
            expect($this->writer)->toReceive('colors')->with('<red>Red text</end><eol>')->once();

            $this->formatter->red('Red text', true);
        });

        it('writes green with eol', function () {
            expect($this->writer)->toReceive('colors')->with('<green>Green text</end><eol>')->once();

            $this->formatter->green('Green text', true);
        });

        it('writes blue with eol', function () {
            expect($this->writer)->toReceive('colors')->with('<blue>Blue text</end><eol>')->once();

            $this->formatter->blue('Blue text', true);
        });
    });

    describe('write and eol methods', function () {
        it('writes text without eol', function () {
            expect($this->writer)->toReceive('write')->with('Text', false)->once();

            $this->formatter->write('Text');
        });

        it('writes text with eol', function () {
            expect($this->writer)->toReceive('write')->with('Text', true)->once();

            $this->formatter->write('Text', true);
        });

        it('adds multiple end of lines', function () {
            expect($this->writer)->toReceive('eol')->with(3)->once();

            $this->formatter->eol(3);
        });

        it('adds new line', function () {
            expect($this->writer)->toReceive('eol')->with(1)->once();

            $this->formatter->newLine();
        });
    });

    describe('list edge cases', function () {
        it('handles empty bullet list', function () {
            expect($this->writer)->not->toReceive('write')->with(Arg::toContain('•'));

            $this->formatter->bulletList([]);
        });

        it('handles bullet list with title only', function () {
            expect($this->writer)->toReceive('colors')->with('<yellow>Title:</end>')->once();

            $this->formatter->bulletList([], 'Title:');
        });

        it('handles empty numbered list', function () {
            expect($this->writer)->not->toReceive('colors')->with(Arg::toContain('<green>'));

            $this->formatter->numberedList([]);
        });

        it('handles numbered list with custom title color', function () {
            expect($this->writer)->toReceive('colors')->with('<blue>Numbers:</end>')->once();

            $this->formatter->numberedList(['One'], 'Numbers:', 'blue');
        });
    });
});
