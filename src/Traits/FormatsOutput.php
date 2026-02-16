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

use Ahc\Cli\Output\Writer;

/**
 * Provides methods for formatting console output.
 *
 * @property Writer $writer
 *
 * @mixin \Dimtrovich\Console\Command
 */
trait FormatsOutput
{
    /**
     * Write a message with optional color.
     *
     * @param string      $message   Message to write
     * @param string|null $color     Color name
     * @param int         $verbosity Verbosity level
     */
    public function line(string $message, ?string $color = null, int $verbosity = 1): self
    {
        if ($color !== null) {
            return $this->colorize($message, $color);
        }

        return $this->write($message)->eol();
    }

    /**
     * Write an informational message.
     *
     * @param string $message Informational message
     */
    public function info(string $message): self
    {
        return $this->iinfo($message)->eol();
    }

    /**
     * Write an informational message inline
     */
    public function iinfo(string $message): self
    {
        $this->writer->info($message, false);

        return $this;
    }

    /**
     * Write a success message.
     *
     * @param string $message Success message
     */
    public function success(string $message): self
    {
        return $this->isuccess($message)->eol();
    }

    /**
     * Write a success message inline.
     *
     * @param string $message Success message
     */
    public function isuccess(string $message): self
    {
        $this->writer->ok($message, false);

        return $this;
    }

    /**
     * Write a warning message.
     *
     * @param string $message Warning message
     */
    public function warn(string $message): self
    {
        return $this->iwarn($message)->eol();
    }

    /**
     * Write a warning message inline.
     *
     * @param string $message Warning message
     */
    public function iwarn(string $message): self
    {
        $this->writer->warn($message, false);

        return $this;
    }

    /**
     * Write a warning message (alias for warn).
     *
     * @param string $message Warning message
     */
    public function warning(string $message): self
    {
        return $this->warn($message);
    }

    /**
     * Write a warning message inline (alias for warn).
     *
     * @param string $message Warning message
     */
    public function iwarning(string $message): self
    {
        return $this->iwarn($message);
    }

    /**
     * Write an error message.
     *
     * @param string $message Error message
     */
    public function error(string $message): self
    {
        return $this->ierror($message)->eol();
    }

    /**
     * Write an error message inline.
     *
     * @param string $message Error message
     */
    public function ierror(string $message): self
    {
        $this->writer->error($message, false);

        return $this;
    }

    /**
     * Write a comment message.
     *
     * @param string $message Comment message
     */
    public function comment(string $message): self
    {
        return $this->icomment($message)->eol();
    }

    /**
     * Write a comment message inline.
     *
     * @param string $message Comment message
     */
    public function icomment(string $message): self
    {
        $this->writer->comment($message, false);

        return $this;
    }

    /**
     * Write a question message.
     *
     * @param string $message Question message
     */
    public function question(string $message): self
    {
        return $this->iquestion($message)->eol();
    }

    /**
     * Write a question message inline.
     *
     * @param string $message Question message
     */
    public function iquestion(string $message): self
    {
        $this->writer->question($message, false);

        return $this;
    }

    /**
     * Write an OK message.
     *
     * @param string $message OK message
     */
    public function ok(string $message): self
    {
        return $this->iok($message)->eol();
    }

    /**
     * Write an OK message inline.
     *
     * @param string $message OK message
     */
    public function iok(string $message): self
    {
        $this->writer->ok($message, false);

        return $this;
    }

    /**
     * Write a note message.
     *
     * @param string $message Note message
     */
    public function note(string $message): self
    {
        $this->writer->comment('NOTE: ' . $message)->eol();

        return $this;
    }

    /**
     * Write a notice message.
     *
     * @param string $message Notice message
     */
    public function notice(string $message): self
    {
        $this->writer->info('NOTICE: ' . $message)->eol();

        return $this;
    }

    /**
     * Write a caution message.
     *
     * @param string $message Caution message
     */
    public function caution(string $message): self
    {
        $this->writer->warn('CAUTION: ' . $message)->eol();

        return $this;
    }

    /**
     * Write a debug message.
     *
     * @param string $message Debug message
     */
    public function debug(string $message): self
    {
        $this->writer->comment('DEBUG: ' . $message)->eol();

        return $this;
    }

    /**
     * Write a fail message.
     *
     * @param string $message Fail message
     */
    public function fail(string $message): self
    {
        $this->writer->error('FAIL: ' . $message)->eol();

        return $this;
    }

    /**
     * Write a message with specific color.
     *
     * @param string $message Message to colorize
     * @param string $style   Color or style name
     * @param bool   $eol     Whether to add end of line
     */
    public function colorize(string $message, string $style, bool $eol = false): self
    {
        $this->writer->colors('<' . $style . '>' . $message . '</end>' . ($eol ? '<eol>' : ''));

        return $this;
    }

    /**
     * Write a message in bold.
     *
     * @param string $message Message to write
     * @param bool   $eol     Whether to add end of line
     */
    public function bold(string $message, bool $eol = false): self
    {
        $this->writer->bold($message, $eol);

        return $this;
    }

    /**
     * Write a message in italic.
     *
     * @param string $message Message to write
     * @param bool   $eol     Whether to add end of line
     */
    public function italic(string $message, bool $eol = false): self
    {
        return $this->colorize($message, 'italic', $eol);
    }

    /**
     * Write a message with underline.
     *
     * @param string $message Message to write
     * @param bool   $eol     Whether to add end of line
     */
    public function underline(string $message, bool $eol = false): self
    {
        return $this->colorize($message, 'underline', $eol);
    }

    /**
     * Write a message with strikethrough.
     *
     * @param string $message Message to write
     * @param bool   $eol     Whether to add end of line
     */
    public function strike(string $message, bool $eol = false): self
    {
        return $this->colorize($message, 'strike', $eol);
    }

    /**
     * Write a message in red.
     *
     * @param string $message Message to write
     * @param bool   $eol     Whether to add end of line
     */
    public function red(string $message, bool $eol = false): self
    {
        return $this->colorize($message, 'red', $eol);
    }

    /**
     * Write a message in green.
     *
     * @param string $message Message to write
     * @param bool   $eol     Whether to add end of line
     */
    public function green(string $message, bool $eol = false): self
    {
        return $this->colorize($message, 'green', $eol);
    }

    /**
     * Write a message in blue.
     *
     * @param string $message Message to write
     * @param bool   $eol     Whether to add end of line
     */
    public function blue(string $message, bool $eol = false): self
    {
        return $this->colorize($message, 'blue', $eol);
    }

    /**
     * Write a message in yellow.
     *
     * @param string $message Message to write
     * @param bool   $eol     Whether to add end of line
     */
    public function yellow(string $message, bool $eol = false): self
    {
        return $this->colorize($message, 'yellow', $eol);
    }

    /**
     * Write a message in magenta.
     *
     * @param string $message Message to write
     * @param bool   $eol     Whether to add end of line
     */
    public function magenta(string $message, bool $eol = false): self
    {
        return $this->colorize($message, 'magenta', $eol);
    }

    /**
     * Write a message in cyan.
     *
     * @param string $message Message to write
     * @param bool   $eol     Whether to add end of line
     */
    public function cyan(string $message, bool $eol = false): self
    {
        return $this->colorize($message, 'cyan', $eol);
    }

    /**
     * Write a message in gray.
     *
     * @param string $message Message to write
     * @param bool   $eol     Whether to add end of line
     */
    public function gray(string $message, bool $eol = false): self
    {
        return $this->colorize($message, 'gray', $eol);
    }

    /**
     * Write a message in black.
     *
     * @param string $message Message to write
     * @param bool   $eol     Whether to add end of line
     */
    public function black(string $message, bool $eol = false): self
    {
        return $this->colorize($message, 'black', $eol);
    }

    /**
     * Write a message in white.
     *
     * @param string $message Message to write
     * @param bool   $eol     Whether to add end of line
     */
    public function white(string $message, bool $eol = false): self
    {
        return $this->colorize($message, 'white', $eol);
    }

    /**
     * Write a message in purple.
     *
     * @param string $message Message to write
     * @param bool   $eol     Whether to add end of line
     */
    public function purple(string $message, bool $eol = false): self
    {
        return $this->colorize($message, 'purple', $eol);
    }

    /**
     * Write a message in indigo.
     *
     * @param string $message Message to write
     * @param bool   $eol     Whether to add end of line
     */
    public function indigo(string $message, bool $eol = false): self
    {
        return $this->colorize($message, 'indigo', $eol);
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
     * Display a bullet list.
     *
     * @param list<string> $items List items
     * @param string       $title List title
     * @param string       $color Title color
     */
    public function bulletList(array $items, string $title = '', string $color = 'yellow'): self
    {
        if ($title !== '') {
            $this->colorize($title, $color);
        }

        foreach ($items as $item) {
            $this->write("  • {$item}")->eol();
        }

        return $this;
    }

    /**
     * Display a numbered list.
     *
     * @param list<string> $items List items
     * @param string       $title List title
     * @param string       $color Title color
     */
    public function numberedList(array $items, string $title = '', string $color = 'yellow'): self
    {
        if ($title !== '') {
            $this->colorize($title, $color);
        }

        foreach ($items as $index => $item) {
            $this->writer->colors(sprintf('  <green>%d.</end> %s', $index + 1, $item))->eol();
        }

        return $this;
    }
}
