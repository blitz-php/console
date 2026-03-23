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

namespace Dimtrovich\Console\Components;

use Ahc\Cli\Output\ProgressBar as AhcProgressBar;
use Ahc\Cli\Output\Writer;

use function Ahc\Cli\t;

/**
 * Extended progress bar with additional features.
 */
class ProgressBar extends AhcProgressBar
{
    /**
     * Progress messages.
     *
     * @var list<string>
     */
    protected array $messages = [];

    /**
     * Start time.
     */
    protected float $startTime;

    /**
     * Current progress value.
     */
    protected int $current = 0;

    /**
     * Create a new progress bar.
     *
     * @param int|null    $total  Total steps
     * @param Writer|null $writer Writer instance
     */
    public function __construct(?int $total = null, ?Writer $writer = null)
    {
        parent::__construct($total, $writer);

        $this->startTime = microtime(true);
    }

    /**
     * Advance progress with a custom message.
     *
     * @param int    $step    Steps to advance
     * @param string $message Progress message
     */
    public function advanceWithMessage(int $step = 1, string $message = ''): void
    {
        if ($message !== '') {
            $this->messages[] = $message;
        }

        $this->advance($step, $message);
    }

    /**
     * Display progress statistics.
     */
    public function showStats(): void
    {
        $elapsed = microtime(true) - $this->startTime;
        $speed   = $this->current > 0 ? $this->current / $elapsed : 0;

        $this->writer->colors(sprintf(
            "\n<yellow>%s:</end> %d items in %.2fs (%.2f items/s)",
            t('Statistics'),
            $this->current,
            $elapsed,
            $speed,
        ))->eol();

        if (! empty($this->messages)) {
            $this->writer->colors('<yellow>' . t('Messages') . ':</end>');

            foreach ($this->messages as $message) {
                $this->writer->write('  • ' . $message)->eol();
            }
        }
    }

    /**
     * Display progress bar with percentage.
     */
    public function display(): void
    {
        if ($this->total) {
            $percent   = (int) (($this->current / $this->total) * 100);
            $barLength = 50;
            $filled    = (int) ($barLength * $percent / 100);

            $bar = str_repeat('█', $filled) . str_repeat('░', $barLength - $filled);
            $this->writer->write(sprintf("\r[%s] %3d%%", $bar, $percent));
        }
    }
}
