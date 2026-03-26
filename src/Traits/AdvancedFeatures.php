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

use Throwable;

use function Ahc\Cli\t;

/**
 * Provides advanced console features and visualizations.
 *
 * @mixin \Dimtrovich\Console\Command
 */
trait AdvancedFeatures
{
    use InteractsWithInput;
    use InteractsWithOutput;

    // =========================================================================
    // Time and Waiting Methods
    // =========================================================================

    /**
     * Wait for a certain number of seconds.
     *
     * @param int    $seconds   Number of seconds to wait
     * @param bool   $countdown Whether to show countdown
     * @param string $waitMsg   Wait message
     */
    public function wait(int $seconds, bool $countdown = false, string $waitMsg = 'Press any key to continue...'): void
    {
        if ($countdown) {
            $time = $seconds;

            while ($time > 0) {
                $this->writer->raw($time . '... ');
                sleep(1);
                $time--;
            }

            $this->writer->raw('' . PHP_EOL);
        } elseif ($seconds > 0) {
            sleep($seconds);
        } else {
            $this->writer->raw($waitMsg . ' ');
            $this->reader->read();
        }
    }

    /**
     * Pause execution until key press.
     *
     * @param string $message Pause message
     */
    public function pause(string $message = 'Press any key to continue...'): void
    {
        $this->wait(0, false, $message);
    }

    // =========================================================================
    // Progress and Loading Methods
    // =========================================================================

    /**
     * Execute a callback with a spinner animation.
     *
     * This method displays an animated spinner while executing a callback.
     * If pcntl_fork is available, the callback runs in a child process for true parallelism.
     * Otherwise, it runs synchronously with cursor hiding.
     *
     * @param callable $callback The callback function to execute
     * @param string   $message  The message to display next to the spinner
     *
     * @return mixed The result of the callback execution
     *
     * @example
     * ```php
     * $result = $this->withSpinner(function() {
     *     sleep(3);
     *     return 'Task completed';
     * }, 'Processing long task...');
     * ```
     */
    public function withSpinner(callable $callback, string $message = 'Processing...'): mixed
    {
        $spinner = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];
        $this->write($message . ' ');

        $result   = null;
        $finished = false;

        $startTime = microtime(true);
        $i         = 0;

        // Execute in a separate process or with pcntl_fork if available
        if (function_exists('pcntl_fork')) {
            $pid = pcntl_fork();

            if ($pid === 0) {
                // Child process - execute callback
                try {
                    $result = $callback();

                    exit(0);
                } catch (Throwable) {
                    exit(1);
                }
            }

            // Parent process - display spinner
            while (pcntl_waitpid($pid, $status, WNOHANG) === 0) {
                $this->write($this->cursor->left(1));
                $this->write($spinner[$i % count($spinner)]);
                usleep(100000);
                $i++;
            }

            $this->write($this->cursor->left(1))->write('✓')->eol();
        } else {
            $this->write($this->cursor->hide());
            $result = $callback();
            $this->write($this->cursor->show());
            $this->write($this->cursor->left(1))->write('✓')->eol();
        }

        return $result;
    }

    /**
     * Execute a task with a progress bar.
     *
     * This method creates and manages a progress bar while executing a callback
     * either for each item in an iterable or for a fixed number of steps.
     *
     * @param int|iterable $items    Items to process (iterable) or total steps (int)
     * @param callable     $callback Callback that receives (item, progressBar, key) for iterables,
     *                               or (progressBar) for integer totals
     *
     * @example
     * ```php
     * // With array
     * $this->withProgressBar([1,2,3,4,5], function($item, $bar) {
     *     process($item);
     * });
     *
     * // With total count
     * $this->withProgressBar(10, function($bar) {
     *     doWork();
     *     $bar->advance();
     * });
     * ```
     */
    public function withProgressBar(int|iterable $items, callable $callback): void
    {
        $bar = $this->progress(is_iterable($items) ? count($items) : $items);

        if (is_iterable($items)) {
            foreach ($items as $key => $value) {
                $callback($value, $bar, $key);
                $bar->advance();
            }
        } else {
            $callback($bar);
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Display a live counter with real-time updates.
     *
     * Shows a continuously updating counter that refreshes at specified intervals.
     * Useful for monitoring progress of long-running processes.
     *
     * @param callable $updater  Callback that returns the current value (receives step index)
     * @param int      $step     Number of steps/updates to perform
     * @param string   $label    Label to display before the counter value
     * @param int      $interval Update interval in microseconds
     *
     * @example
     * ```php
     * $this->liveCounter(function($i) {
     *     return getCurrentProgress();
     * }, 20, 'Progress', 500000);
     * ```
     */
    public function liveCounter(callable $updater, int $step = 10, string $label = 'Counter', int $interval = 1000000): void
    {
        $this->write($this->cursor->hide());

        for ($i = 0; $i < $step; $i++) {
            $value = $updater($i);
            $this->write($this->cursor->left(20));
            $this->write("{$label}: " . str_pad((string) $value, 10));
            usleep($interval);
        }

        $this->write($this->cursor->show())->eol();
    }

    // =========================================================================
    // Visualizations and Graphics
    // =========================================================================

    /**
     * Display a timeline of events with status indicators.
     *
     * Shows a chronological list of events with visual status indicators
     * (✓ completed, ✗ failed, ↻ processing, ○ pending).
     *
     * @param list<array{status?: string, description?: string}> $events Timeline events
     *                                                                   Each event can have:
     *                                                                   - status: 'completed', 'failed', 'processing', or any other string (default: 'pending')
     *                                                                   - description: Event description (default: 'Event N')
     *
     * @example
     * ```php
     * $this->timeline([
     *     ['status' => 'completed', 'description' => 'Database migrated'],
     *     ['status' => 'processing', 'description' => 'Cache cleared'],
     *     ['status' => 'pending', 'description' => 'Assets compiled'],
     * ]);
     * ```
     */
    public function timeline(array $events): self
    {
        $this->colorize('Timeline:', 'yellow');

        foreach ($events as $index => $event) {
            $status = $event['status'] ?? 'pending';
            $icon   = match ($status) {
                'completed'  => '✓',
                'failed'     => '✗',
                'processing' => '↻',
                default      => '○'
            };

            $color = match ($status) {
                'completed'  => 'green',
                'failed'     => 'red',
                'processing' => 'yellow',
                default      => 'gray'
            };

            $this->writer->colors(sprintf(
                '  <%s>%s</end> %s',
                $color,
                $icon,
                $event['description'] ?? 'Event ' . ($index + 1)
            ))->eol();
        }

        return $this;
    }

    /**
     * Display an ASCII heatmap from numerical data.
     *
     * Converts an array of numbers into a visual heatmap using density characters.
     * Values are normalized between min and max of the dataset.
     *
     * @param list<float|int> $data   Array of numerical values
     * @param list<string>    $colors Array of density characters from low to high
     *                                (default: ['░', '▒', '▓', '█'])
     *
     * @example
     * ```php
     * $this->heatmap([10, 20, 5, 30, 15]);
     * // Output: ░▒▓█▒
     * ```
     */
    public function heatmap(array $data, array $colors = ['░', '▒', '▓', '█']): self
    {
        if ($data === []) {
            return $this;
        }

        $max   = max($data);
        $min   = min($data);
        $range = $max - $min;

        foreach ($data as $value) {
            $percentage = $range > 0 ? ($value - $min) / $range : 0.5;
            $index      = (int) ($percentage * (count($colors) - 1));
            $this->write($colors[$index]);
        }

        return $this->newLine();
    }

    /**
     * Display data in a formatted grid.
     *
     * Renders a 2D array as an aligned grid with automatic column width calculation.
     * Optional formatter callback allows custom cell formatting.
     *
     * @param list<list<mixed>> $data      Grid data as array of rows
     * @param callable|null     $formatter Optional callback to format each cell
     *                                     Receives cell value, returns formatted string
     *
     * @example
     * ```php
     * $data = [
     *     ['Name', 'Age', 'City'],
     *     ['John', 30, 'New York'],
     *     ['Jane', 25, 'London']
     * ];
     *
     * $this->grid($data, fn($cell) => strtoupper((string)$cell));
     * ```
     */
    public function grid(array $data, ?callable $formatter = null): self
    {
        $formatter ??= fn ($cell) => $cell;

        $colWidths = [];

        foreach ($data as $row) {
            foreach ($row as $colIndex => $cell) {
                $width                = strlen((string) $formatter($cell));
                $colWidths[$colIndex] = max($colWidths[$colIndex] ?? 0, $width);
            }
        }

        foreach ($data as $row) {
            $line = '';

            foreach ($row as $colIndex => $cell) {
                $formatted = $formatter($cell);
                $line .= str_pad((string) $formatted, $colWidths[$colIndex] + 2);
            }

            $this->write($line)->eol();
        }

        return $this;
    }

    /**
     * Display a chart in ASCII format.
     *
     * Creates either a bar chart or pie chart visualization from associative data.
     *
     * @param array<string, float|int> $data   Associative array of labels and values
     * @param string                   $type   Chart type: 'bar' or 'pie'
     * @param int                      $height Maximum height/width of the chart
     *
     * @example
     * ```php
     * // Bar chart
     * $this->chart(['A' => 10, 'B' => 20, 'C' => 5], 'bar');
     *
     * // Pie chart
     * $this->chart(['Linux' => 50, 'Windows' => 30, 'Mac' => 20], 'pie');
     * ```
     */
    public function chart(array $data, string $type = 'bar', int $height = 10): self
    {
        if ($data === []) {
            return $this;
        }

        $max = max($data);

        if ($type === 'bar') {
            foreach ($data as $label => $value) {
                $barLength = (int) (($value / $max) * $height * 2);
                $bar       = str_repeat('█', $barLength);
                $this->write(sprintf('%-20s %s %d', $label, $bar, $value))->eol();
            }
        } elseif ($type === 'pie') {
            $total = array_sum($data);
            $this->colorize(t('Pie Chart'), 'yellow');

            foreach ($data as $label => $value) {
                $percentage = ($value / $total) * 100;
                $this->write(sprintf('  %s: %.1f%%', $label, $percentage))->eol();
            }
        }

        return $this;
    }

    // =========================================================================
    // Interactive Menus
    // =========================================================================

    /**
     * Display an interactive menu.
     *
     * Shows a menu with numbered options and prompts the user to choose.
     * Returns the selected option value.
     *
     * @param string               $title   Menu title
     * @param array<string, mixed> $options Menu options where keys are option identifiers
     *                                      and values can be strings or arrays with 'label'
     * @param string|null          $default Default option key
     *
     * @return mixed Selected option value
     *
     * @example
     * ```php
     * $choice = $this->menu('Actions', [
     *     '1' => ['label' => 'Create user'],
     *     '2' => ['label' => 'Delete user'],
     *     '3' => 'Exit'
     * ], '1');
     * ```
     */
    public function menu(string $title, array $options, ?string $default = null): mixed
    {
        $this->colorize($title, 'yellow')->eol();

        foreach ($options as $key => $option) {
            $this->writer->colors(sprintf('  <green>%s</end> %s', $key, $option['label'] ?? $option))->eol();
        }

        $choice = $this->ask(t('Choose an option') . ' :', $default) ?? $default;

        return $options[$choice] ?? $choice;
    }

    // =========================================================================
    // Animation and Sound
    // =========================================================================

    /**
     * Display an animation sequence.
     *
     * Shows a sequence of frames in a loop to create an animation effect.
     *
     * @param list<string> $frames     Array of animation frames (strings)
     * @param int          $iterations Number of times to loop through frames
     * @param int          $delay      Delay between frames in microseconds
     *
     * @example
     * ```php
     * $this->animation(['◐', '◓', '◑', '◒'], 5, 100000);
     * ```
     */
    public function animation(array $frames, int $iterations = 3, int $delay = 100000): self
    {
        $this->write($this->cursor->hide());

        for ($i = 0; $i < $iterations; $i++) {
            foreach ($frames as $frame) {
                $this->write($this->cursor->eraseLine());
                $this->write($frame);
                usleep($delay);
            }
        }

        $this->write($this->cursor->show());

        return $this;
    }

    /**
     * Play a beep sound.
     *
     * Outputs the ASCII bell character to produce a system beep sound.
     *
     * @param int $count Number of beeps to play
     *
     * @example
     * ```php
     * $this->beep(3); // Beep three times
     * ```
     */
    public function beep(int $count = 1): self
    {
        for ($i = 0; $i < $count; $i++) {
            $this->write("\x07");
            usleep(200000);
        }

        return $this;
    }

    // =========================================================================
    // System Integration
    // =========================================================================

    /**
     * Display a system notification.
     *
     * Sends a desktop notification using platform-specific commands:
     * - macOS: osascript
     * - Linux: notify-send
     * - Windows: PowerShell beep
     *
     * @param string $title   Notification title
     * @param string $message Notification message
     *
     * @example
     * ```php
     * $this->notify('Task Complete', 'The backup has finished successfully');
     * ```
     */
    public function notify(string $title, string $message): self
    {
        $os = PHP_OS_FAMILY;

        if ($os === 'Darwin') {
            exec("osascript -e 'display notification \"{$message}\" with title \"{$title}\"'");
        } elseif ($os === 'Linux') {
            exec("notify-send \"{$title}\" \"{$message}\"");
        } elseif ($os === 'Windows') {
            exec('powershell -Command "[System.Media.SystemSounds]::Beep.Play()"');
        }

        return $this;
    }
}
