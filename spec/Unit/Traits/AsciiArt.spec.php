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
use Dimtrovich\Console\Traits\AsciiArt;

use function Kahlan\expect;

describe('Traits / AsciiArt', function () {
    fileHook(
        file: 'output-asciiart.test',
        beforeAll: function () {
            $this->getArt = function ($writer) {
                $art = new class () {
                    use AsciiArt;

                    protected $writer;

                    public function setWriter($w)
                    {
                        $this->writer = $w;

                        return $this;
                    }

                    public function write(string $text, bool $eol = false)
                    {
                        $this->writer->write($text, $eol);

                        return $this;
                    }

                    public function colorize(string $message, string $style, bool $eol = false)
                    {
                        $this->writer->colors('<' . $style . '>' . $message . '</end>' . ($eol ? '<eol>' : ''));

                        return $this;
                    }

                    public function newLine()
                    {
                        $this->writer->eol();

                        return $this;
                    }
                };

                return $art->setWriter($writer);
            };
        },
        beforeEach: function ($files) {
            $this->writer = new Writer($files[0]);
            $this->art    = $this->getArt($this->writer);
        },
    );

    describe('font management', function () {
        it('has default fonts available', function () {
            $fonts = $this->art->getAvailableFonts();

            expect($fonts)->toContain('standard');
            expect($fonts)->toContain('minimal');
        });

        it('checks if font exists', function () {
            expect($this->art->hasFont('standard'))->toBe(true);
            expect($this->art->hasFont('nonexistent'))->toBe(false);
        });

        it('sets current font with withFont()', function () {
            $result = $this->art->withFont('minimal');

            expect($result)->toBe($this->art);

            // check that font it's using
            expect($this->writer)->toReceive('write')->with('▲', true)->once();

            $this->art->asciiArt('A');
        });

        it('throws exception for invalid font', function () {
            expect(function () {
                $this->art->withFont('invalid');
            })->toThrow(new InvalidArgumentException(
                'ASCII font "invalid" not found. Available fonts: standard, minimal',
            ));
        });

        it('registers custom font', function () {
            $customFont = [
                'A' => '[A]',
                'B' => '[B]',
            ];

            $result = $this->art->registerFont('custom', $customFont);

            expect($result)->toBe($this->art);
            expect($this->art->hasFont('custom'))->toBe(true);

            expect($this->writer)->toReceive('write')->with('[A]', true)->once();

            $this->art->asciiArt('A', 'custom');

            $this->art->unregisterFont('custom');
        });

        it('loads fonts from directory', function () {
            // create a temp directory with font files
            $tempDir = sys_get_temp_dir() . '/fonts_' . uniqid();
            mkdir($tempDir);

            file_put_contents($tempDir . '/testfont.php', '<?php return ["A" => "T"];');

            $count = $this->art->loadFonts($tempDir);

            expect($count)->toBe(1);
            expect($this->art->hasFont('testfont'))->toBe(true);

            $this->art->unregisterFont('testfont');

            // clean
            unlink($tempDir . '/testfont.php');
            rmdir($tempDir);
        });

        it('returns 0 when loading from invalid directory', function () {
            $count = $this->art->loadFonts('/nonexistent/directory');

            expect($count)->toBe(0);
        });
    });

    describe('ascii art rendering', function () {
        it('renders text with standard font', function () {
            expect($this->writer)->toReceive('write')->with('  ██  ', true)->once();

            $this->art->asciiArt('A');
        });

        it('renders text with specific font', function () {
            expect($this->writer)->toReceive('write')->with('▲', true)->once();

            $this->art->asciiArt('A', 'minimal');
        });

        it('handles multiple characters', function () {
            expect($this->writer)->toReceive('write')->times(2); // H et I

            $this->art->asciiArt('HI');
        });

        it('uses space for unknown characters', function () {
            $this->art->registerFont('test', [' ' => ' ']);

            expect($this->writer)->toReceive('write')->with(' ', true)->once();

            $this->art->asciiArt('@', 'test');

            $this->art->unregisterFont('test');
        });

        it('throws exception for invalid font in asciiArt', function () {
            expect(function () {
                $this->art->asciiArt('TEST', 'invalid');
            })->toThrow(new InvalidArgumentException(
                'ASCII font "invalid" not found. Available fonts: standard, minimal',
            ));
        });
    });

    describe('preview and banner', function () {
        it('previews font', function () {
            expect($this->writer)->toReceive('colors')->with('<yellow>Preview of font \'standard\':</end>')->once();
            expect($this->writer)->toReceive('write')->times(27); // A-Z + PHP_EOL

            $this->art->previewFont('standard');
        });

        it('previews font with custom sample', function () {
            expect($this->writer)->toReceive('write')->times(4); // A, B, C + PHP_EOL

            $this->art->previewFont('standard', 'ABC');
        });

        it('creates banner', function () {
            // expect($this->writer)->toReceive('write')->with('***********', true); // ->twice(); // top et bottom
            expect($this->writer)->toReceive('write')->with('█ █ █', true)->once(); // W

            $this->art->banner('W', '*', 'standard');
        });
    });
});
