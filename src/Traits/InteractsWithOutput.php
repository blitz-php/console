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

use Ahc\Cli\Helper\InflectsString;
use Ahc\Cli\Helper\Terminal;
use Ahc\Cli\Output\Color;
use Ahc\Cli\Output\Writer;
use Dimtrovich\Console\Components\Alert;
use Dimtrovich\Console\Components\Badge;
use Dimtrovich\Console\Components\Logger;
use Dimtrovich\Console\Components\ProgressBar;
use Dimtrovich\Console\Overrides\Cursor;
use RuntimeException;

use function Ahc\Cli\t;

/**
 * Provides interaction with console output.
 *
 * @property Color    $color
 * @property Cursor   $cursor
 * @property Terminal $terminal
 * @property Writer   $writer
 *
 * @mixin \Dimtrovich\Console\Command
 */
trait InteractsWithOutput
{
    use FormatsOutput;
    use InflectsString;

    /**
     * Get the Alert component instance.
     */
    public function alert(): Alert
    {
        return Alert::instance($this->writer);
    }

    /**
     * Get the Badge component instance.
     */
    public function badge(): Badge
    {
        return Badge::instance($this->writer);
    }

    /**
     * Get the Logger component instance.
     *
     * This method provides access to the logging system, which combines
     * console output with PSR-3 logging. Each log message will be:
     * - Displayed in the console with appropriate styling and icons
     * - Sent to the configured PSR logger with an optional prefix
     *
     * @param string $prefix Optional prefix for this logger instance.
     *                       If different from current prefix, returns a new instance.
     *                       Use empty string to get the default logger.
     *
     * @return Logger The logger instance
     *
     * @example
     * ```php
     * // Basic usage with default prefix
     * $this->log()->info('User logged in');
     *
     * // With specific prefix for this block
     * $dbLogger = $this->log('DB');
     * $dbLogger->debug('Connecting to database');
     *
     * // Chained prefixes
     * $this->log('APP')
     *      ->withPrefix('CACHE')
     *      ->info('Cache cleared');
     * // Console: [APP > CACHE] Cache cleared
     * ```
     */
    public function log(string $prefix = ''): Logger
    {
        if (! Logger::hasLogger()) {
            throw new RuntimeException(t('No PSR logger configured. Use $app->withLogger() to set one.'));
        }

        $logger = Logger::instance($this->writer);

        if ($prefix !== '' && $prefix !== $logger->prefix()) {
            return new Logger($this->writer, $prefix);
        }

        return $logger;
    }

    /**
     * Display currently executing task.
     *
     * @param string   $task  Task description
     * @param int|null $sleep Sleep duration in seconds
     */
    public function task(string $task, ?int $sleep = null): self
    {
        $this->write('>> ' . $task, true);

        if ($sleep !== null) {
            sleep($sleep);
        }

        return $this;
    }

    /**
     * Display a counter with animation.
     *
     * @param int $start Counter start value
     * @param int $end   Counter end value
     * @param int $step  Counter step value
     */
    public function counter(int $start = 0, int $end = 100, int $step = 1): void
    {
        for ($i = $start; $i <= $end; $i += $step) {
            $this->write($this->cursor->col(-4))->write(sprintf('%3d%%', $i));
            usleep(50000);
        }

        $this->eol();
    }

    /**
     * Display a table.
     *
     * Supports two signatures:
     * 1. Old signature: table(array $rows, array $styles = [])
     *    Where $rows is already in the format expected by adhocore/cli
     * 2. New signature: table(array $headers, array $rows, array $styles = [])
     *    Where $headers is a 1D array and $rows is a 2D array
     *
     * @param list<array<string, mixed>>|list<string>         $headers Table headers
     * @param array<string, mixed>|list<array<string, mixed>> $rows    Table rows
     * @param array<string, mixed>                            $styles  Table styles (only for new signature)
     */
    public function table(array $headers, array $rows = [], array $styles = []): self
    {
        // Check if this is the old signature (first param is 2D array)
        if ($this->isTwoDimensionalArray($headers)) {
            // Old signature: table($rows, $styles)
            $styles = $rows;
            $rows   = $headers;
        } else {
            // Convert headers and rows to format expected by adhocore/cli
            $rows = $this->formatTableData($headers, $rows);
        }

        $this->writer->table($rows, $styles);

        return $this;
    }

    /**
     * Check if an array is two-dimensional.
     */
    private function isTwoDimensionalArray(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        $firstElement = reset($array);

        // Check if first element is an array (2D)
        if (! is_array($firstElement)) {
            return false;
        }

        // Check if it's an associative array (old format has associative arrays)
        // The old format has rows like ['name' => 'John', 'age' => 30]
        return ! array_is_list($firstElement);
    }

    /**
     * Format table data from header/rows format to adhocore/cli format.
     *
     * @param list<string>             $headers Table headers
     * @param list<list<mixed|string>> $rows    Table rows
     *
     * @return list<array<string, mixed>> Formatted rows
     */
    private function formatTableData(array $headers, array $rows): array
    {
        $formattedRows = [];

        foreach ($rows as $row) {
            $formattedRow = [];

            foreach ($headers as $index => $header) {
                // Use header as key, value from row at same index
                $value                 = $row[$index] ?? '';
                $formattedRow[$header] = $value;
            }

            $formattedRows[] = $formattedRow;
        }

        return $formattedRows;
    }

    /**
     * Display JSON formatted data.
     *
     * @param mixed $data Data to display as JSON
     */
    public function json($data): self
    {
        return $this->write(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))->eol();
    }

    /**
     * Display a border line.
     *
     * @param int|null $length Border length
     * @param string   $char   Border character
     */
    public function border(?int $length = null, string $char = '-'): self
    {
        $length = $length ?: ($this->terminal->width() ?: 100);
        $str    = str_repeat($char, $length);
        $str    = $this->substr($str, 0, $length);

        return $this->comment($str);
    }

    /**
     * Write text with tabs.
     *
     * @param int $repeat Number of tabs
     */
    public function tab(int $repeat = 1): self
    {
        $this->write(str_repeat("\t", $repeat));

        return $this;
    }

    /**
     * Write text at the center of console.
     *
     * @param string               $text    Text to center
     * @param array<string, mixed> $options Center options
     */
    public function center(string $text, array $options = []): self
    {
        $sep = $options['sep'] ?? ' ';
        unset($options['sep']);

        $dashWidth = ($this->terminal->width() ?: 100) - $this->strwidth($text);
        $dashWidth -= 2;
        $dashWidth = (int) ($dashWidth / 2);

        $text     = $this->color->line($text, $options);
        $repeater = str_repeat($sep, $dashWidth);

        return $this->write($repeater . ' ' . $text . ' ' . $repeater)->eol();
    }

    /**
     * Write justified text (left and right aligned).
     *
     * @param string               $first   Left text
     * @param string|null          $second  Right text
     * @param array<string, mixed> $options Justify options
     */
    public function justify(string $first, ?string $second = '', array $options = []): self
    {
        $second = trim($second);
        $first  = trim($first);

        $start = $first;
        $end   = $second;

        $options = [
            'first'  => $options['first'] ?? [],
            'second' => ($options['second'] ?? []) + ['bold' => 1],
            'sep'    => (string) ($options['sep'] ?? '.'),
        ];

        while (preg_match('/(\\x1b(?:.+)m)/U', $first, $matches)) {
            $first = str_replace($matches[1], '', $first);
            $first = preg_replace('/\\x1b\[0m/', '', $first);
        }

        while (preg_match('/(\\x1b(?:.+)m)/U', $second, $matches)) {
            $second = str_replace($matches[1], '', $second);
            $second = preg_replace('/\\x1b\[0m/', '', $second);
        }

        $firstLength  = $this->strwidth($first);
        $secondLength = $this->strwidth($second);

        $dashWidth = ($this->terminal->width() ?: 100) - ($firstLength + $secondLength);
        $dashWidth -= $second === '' ? 1 : 2;
        $dashWidth = $dashWidth < 0 ? 0 : $dashWidth;

        $first = $this->color->line($start, $options['first']);
        if ($second !== '') {
            $second = $this->color->line($end, $options['second']);
        }

        return $this->write($first . ' ' . str_repeat($options['sep'], $dashWidth) . ' ' . $second)->eol();
    }

    /**
     * Initialize a progress bar.
     *
     * @param int|null $total Total steps
     *
     * @return ProgressBar Progress bar instance
     */
    public function progress(?int $total = null): ProgressBar
    {
        return new ProgressBar($total, $this->writer);
    }

    /**
     * Add end of line(s).
     *
     * @param int $n Number of end of lines
     */
    public function eol(int $n = 1): static
    {
        $this->writer->eol($n);

        return $this;
    }

    /**
     * Add a new empty line.
     */
    public function newLine(): self
    {
        return $this->eol(1);
    }

    /**
     * Write text to output.
     *
     * @param string $text Text to write
     * @param bool   $eol  Whether to add end of line
     */
    public function write(string $text, bool $eol = false): self
    {
        $this->writer->write($text, $eol);

        return $this;
    }

    /**
     * Clear the screen of output.
     */
    public function clearScreen(): void
    {
        // Unix systems, and Windows with VT100 Terminal support (i.e. Win10) can handle CSI sequences.
        // For lower than Win10 we just shove in 40 new lines.
        if ($this->terminal->isWindows() && (\function_exists('sapi_windows_vt100_support') && @sapi_windows_vt100_support(STDOUT))) {
            $this->eol(40);
        } else {
            $this->writer->raw("\033[H\033[2J");
        }
    }
}
