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

namespace Dimtrovich\Console\Traits;

use InvalidArgumentException;

use function Ahc\Cli\t;

/**
 * ASCII Art generation trait for console commands.
 *
 * This trait provides methods to render text as ASCII art using various fonts.
 * It's designed to be used optionally by commands that need decorative banners,
 * headers, or stylized text output.
 *
 * @mixin \Dimtrovich\Console\Command
 *
 * @example
 * ```php
 * use Dimtrovich\Console\Command;
 * use Dimtrovich\Console\Traits\AsciiArt;
 *
 * class BannerCommand extends Command
 * {
 *     use AsciiArt; // Optional feature
 *
 *     public function handle()
 *     {
 *         $this->asciiArt('WELCOME', 'standard');
 *         $this->withFont('big')->asciiArt('HELLO');
 *     }
 * }
 * ```
 */
trait AsciiArt
{
    /**
     * Built-in ASCII art fonts collection.
     *
     * @var array<string, array<string, string>>
     */
    protected static array $asciiFonts = [
        // Standard font (95 characters)
        'standard' => [
            // Capital letters
            'A' => '  ██  ',
            'B' => '████ ',
            'C' => ' ████',
            'D' => '████ ',
            'E' => '█████',
            'F' => '█████',
            'G' => ' ████',
            'H' => '█  █',
            'I' => '█████',
            'J' => '  ███',
            'K' => '█ █ ',
            'L' => '█   ',
            'M' => '█ █ █',
            'N' => '█   █',
            'O' => ' ██ ',
            'P' => '████ ',
            'Q' => ' ██ █',
            'R' => '████ ',
            'S' => ' ████',
            'T' => '███  ',
            'U' => '█   █',
            'V' => '█   █',
            'W' => '█ █ █',
            'X' => '█   █',
            'Y' => '█   █',
            'Z' => '███  ',

            // lower letters
            'a' => ' ██  ',
            'b' => '█    ',
            'c' => ' ██  ',
            'd' => '  ██ ',
            'e' => ' ██  ',
            'f' => ' ██  ',
            'g' => ' ██ █',
            'h' => '█    ',
            'i' => '  █  ',
            'j' => '   █ ',
            'k' => '█ █  ',
            'l' => '█    ',
            'm' => '█ █ █',
            'n' => '█    ',
            'o' => ' ██  ',
            'p' => '███  ',
            'q' => ' ██ █',
            'r' => '█    ',
            's' => ' ███ ',
            't' => ' ██  ',
            'u' => '█   █',
            'v' => '█   █',
            'w' => '█ █ █',
            'x' => '█   █',
            'y' => '█   █',
            'z' => '███  ',

            // Chiffres
            '0' => ' ██ ',
            '1' => '  █ ',
            '2' => '███ ',
            '3' => '███ ',
            '4' => '█ █ ',
            '5' => '████',
            '6' => ' ██ ',
            '7' => '███ ',
            '8' => ' ██ ',
            '9' => ' ██ ',

            // Ponctuation et symboles
            ' '  => '    ',
            '.'  => '   ',
            ','  => '   ',
            '?'  => '██  ',
            '!'  => '█   ',
            ':'  => '    ',
            ';'  => '    ',
            '-'  => '    ',
            '_'  => '    ',
            '+'  => '  █  ',
            '='  => '     ',
            '*'  => '█ █ █',
            '/'  => '   █ ',
            '\\' => '█   ',
            '|'  => '█   ',
            '('  => ' █  ',
            ')'  => '  █ ',
            '['  => '██  ',
            ']'  => '██  ',
            '{'  => ' █  ',
            '}'  => '  █ ',
            '<'  => '  █ ',
            '>'  => ' █  ',
            '@'  => ' ██ █',
            '#'  => '█ █ █',
            '$'  => '█ ██',
            '%'  => '█ █ █',
            '^'  => ' █  ',
            '&'  => ' ██ ',
            '~'  => '     ',
            '`'  => '█    ',
            "'"  => '█    ',
            '"'  => '█ █  ',
        ],

        // Minimal font (compact)
        'minimal' => [
            'A' => '▲',
            'B' => '■',
            'C' => '●',
            'D' => '◆',
            'E' => '▼',
            'F' => '◀',
            'G' => '▶',
            'H' => '◊',
            'I' => '|',
            'J' => '⌐',
            'K' => '⌠',
            'L' => '⌡',
            'M' => '█',
            'N' => '▓',
            'O' => '●',
            'P' => '◙',
            'Q' => '◘',
            'R' => '►',
            'S' => '◄',
            'T' => '◄',
            'U' => '─',
            'V' => '│',
            'W' => '┌',
            'X' => '┐',
            'Y' => '└',
            'Z' => '┘',
            ' ' => ' ',
        ],
    ];

    /**
     * Currently selected font for ASCII art.
     */
    protected string $currentAsciiFont = 'standard';

    /**
     * Set the font to use for subsequent ASCII art rendering.
     *
     * @param string $font Font name
     *
     * @throws InvalidArgumentException If font doesn't exist
     *
     * @example
     * ```php
     * $this->withFont('big')->asciiArt('HELLO');
     * ```
     */
    public function withFont(string $font): self
    {
        if (! isset(static::$asciiFonts[$font])) {
            throw new InvalidArgumentException(
                t('ASCII font "%s" not found. Available fonts: %s', [
                    $font,
                    implode(', ', array_keys(static::$asciiFonts)),
                ]),
            );
        }

        $this->currentAsciiFont = $font;

        return $this;
    }

    /**
     * Register a custom ASCII art font.
     *
     * @param string                $name       Font name
     * @param array<string, string> $characters Mapping of characters to their ASCII representation
     *
     * @example
     * ```php
     * $this->registerFont('big', [
     *     'A' => '  ███  ',
     *     'B' => ' █   █ ',
     *     // ...
     * ]);
     * ```
     */
    public function registerFont(string $name, array $characters): self
    {
        static::$asciiFonts[$name] = $characters;

        return $this;
    }

    /**
     * unegister a custom ASCII art font.
     *
     * @param string $name Font name
     */
    public function unregisterFont(string $name): self
    {
        unset(static::$asciiFonts[$name]);

        return $this;
    }

    /**
     * Load fonts from a directory.
     *
     * Each file should return an array of character mappings.
     * File name becomes the font name (without .php extension).
     *
     * @param string $directory Path to directory containing font files
     *
     * @return int Number of fonts loaded
     *
     * @example
     * ```php
     * $count = $this->loadFonts(__DIR__ . '/fonts/');
     * $this->info("Loaded $count fonts");
     * ```
     */
    public function loadFonts(string $directory): int
    {
        if (! is_dir($directory)) {
            return 0;
        }

        $count = 0;
        $files = glob($directory . '/*.php');

        foreach ($files as $file) {
            $font = require $file;
            if (is_array($font)) {
                $name                      = pathinfo($file, PATHINFO_FILENAME);
                static::$asciiFonts[$name] = $font;
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get list of available fonts.
     *
     * @return list<string> List of font names
     */
    public function getAvailableFonts(): array
    {
        return array_keys(static::$asciiFonts);
    }

    /**
     * Check if a font exists.
     *
     * @param string $name Font name
     */
    public function hasFont(string $name): bool
    {
        return isset(static::$asciiFonts[$name]);
    }

    /**
     * Display ASCII art text.
     *
     * Renders text as ASCII art using the currently selected font.
     *
     * @param string      $text Text to display
     * @param string|null $font Optional font name (uses current font if null)
     *
     * @throws InvalidArgumentException If font doesn't exist
     *
     * @example
     * ```php
     * // With current font
     * $this->asciiArt('HELLO');
     *
     * // With specific font
     * $this->asciiArt('WORLD', 'big');
     *
     * // Chained with font selection
     * $this->withFont('starwars')->asciiArt('FORCE');
     * ```
     */
    public function asciiArt(string $text, ?string $font = null): self
    {
        $fontName = $font ?? $this->currentAsciiFont;

        if (! isset(static::$asciiFonts[$fontName])) {
            throw new InvalidArgumentException(
                t('ASCII font "%s" not found. Available fonts: %s', [
                    $fontName,
                    implode(', ', array_keys(static::$asciiFonts)),
                ]),
            );
        }

        $fontData = static::$asciiFonts[$fontName];
        $lines    = [];
        $chars    = str_split($text);

        // Build each character
        foreach ($chars as $char) {
            if (isset($fontData[$char])) {
                $lines[] = $fontData[$char];
            } elseif (isset($fontData[' '])) {
                $lines[] = $fontData[' '];
            }
        }

        // Display
        foreach ($lines as $line) {
            $this->write($line, true);
        }

        return $this;
    }

    /**
     * Preview a font by rendering sample text.
     *
     * @param string $font   Font name
     * @param string $sample Sample text to render (default: alphabet)
     */
    public function previewFont(string $font, string $sample = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'): self
    {
        $this->colorize("Preview of font '{$font}':", 'yellow');
        $this->asciiArt($sample, $font);
        $this->newLine();

        return $this;
    }

    /**
     * Create a banner with decorative borders.
     *
     * @param string $text Banner text
     * @param string $char Border character
     * @param string $font Font for the text
     *
     * @example
     * ```php
     * $this->banner('WELCOME', '*', 'big');
     * // ********************
     * // *      WELCOME     *
     * // ********************
     * ```
     */
    public function banner(string $text, string $char = '*', string $font = 'standard'): self
    {
        // Rendu temporaire pour mesurer la largeur
        $tempLines = [];
        $fontData  = static::$asciiFonts[$font];
        $chars     = str_split($text);
        $maxWidth  = 0;

        foreach ($chars as $char) {
            if (isset($fontData[$char])) {
                $width    = strlen($fontData[$char]);
                $maxWidth = max($maxWidth, $width);
            }
        }

        $totalWidth = $maxWidth * count($chars) + 4;
        $border     = str_repeat($char, $totalWidth);

        $this->write($border, true);
        $this->write($char . ' ', false);
        $this->asciiArt($text, $font);
        $this->write(' ' . $char, true);
        $this->write($border, true);

        return $this;
    }
}
