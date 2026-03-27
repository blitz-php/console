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

use Ahc\Cli\Output\Color;
use Ahc\Cli\Output\Writer;
use Dimtrovich\Console\Icon;
use Exception;

/**
 * Badge component for console output.
 */
class Badge
{
    use IconTrait;
    use SingletonTrait;

    /**
     * Writer instance.
     */
    private Writer $writer;

    /**
     * Colorizer instance.
     */
    private Color $color;

    /**
     * Create a new badge instance.
     *
     * @param Writer $writer Writer instance
     */
    public function __construct(Writer $writer)
    {
        $this->writer = $writer;
        $this->color  = $this->writer->colorizer();
    }

    /**
     * Write EOL n times.
     */
    public function eol(int $n = 1): self
    {
        $this->writer->eol($n);

        return $this;
    }

    /**
     * Display an info badge.
     *
     * @param string      $message Badge message
     * @param string      $label   Badge label
     * @param string|null $icon    Optional icon to display before the label
     *                             (null = use default if enabled, false = no icon)
     */
    public function info(string $message, string $label = 'INFO', false|string|null $icon = null): self
    {
        $resolvedIcon = $this->resolveIcon($icon, Icon::INFO);

        return $this->render($message, $label, 'boldWhiteBgCyan', $resolvedIcon);
    }

    /**
     * Display an info badge with text in "info" style.
     *
     * @param string      $message Badge message
     * @param string      $label   Badge label
     * @param string|null $icon    Optional icon to display before the label
     *                             (null = use default if enabled, false = no icon)
     */
    public function infoFull(string $message, string $label = 'INFO', false|string|null $icon = null): self
    {
        return $this->info($this->color->info($message), $label, $icon);
    }

    /**
     * Display a success badge.
     *
     * @param string      $message Badge message
     * @param string      $label   Badge label
     * @param string|null $icon    Optional icon to display before the label
     *                             (null = use default if enabled, false = no icon)
     */
    public function success(string $message, string $label = 'SUCCESS', false|string|null $icon = null): self
    {
        $resolvedIcon = $this->resolveIcon($icon, Icon::SUCCESS);

        return $this->render($message, $label, 'boldWhiteBgGreen', $resolvedIcon);
    }

    /**
     * Display an success badge with text in "success" style.
     *
     * @param string      $message Badge message
     * @param string      $label   Badge label
     * @param string|null $icon    Optional icon to display before the label
     *                             (null = use default if enabled, false = no icon)
     */
    public function successFull(string $message, string $label = 'INFO', false|string|null $icon = null): self
    {
        return $this->success($this->color->ok($message), $label, $icon);
    }

    /**
     * Display a warning badge.
     *
     * @param string      $message Badge message
     * @param string      $label   Badge label
     * @param string|null $icon    Optional icon to display before the label
     *                             (null = use default if enabled, false = no icon)
     */
    public function warning(string $message, string $label = 'WARNING', false|string|null $icon = null): self
    {
        $resolvedIcon = $this->resolveIcon($icon, Icon::WARNING);

        return $this->render($message, $label, 'boldWhiteBgYellow', $resolvedIcon);
    }

    /**
     * Display a warning badge with text in warning style.
     *
     * @param string      $message Badge message
     * @param string      $label   Badge label
     * @param string|null $icon    Optional icon to display before the label
     *                             (null = use default if enabled, false = no icon)
     */
    public function warningFull(string $message, string $label = 'WARNING', false|string|null $icon = null): self
    {
        return $this->warning($this->color->warn($message), $label, $icon);
    }

    /**
     * Display an error badge.
     *
     * @param string      $message Badge message
     * @param string      $label   Badge label
     * @param string|null $icon    Optional icon to display before the label
     *                             (null = use default if enabled, false = no icon)
     */
    public function error(string $message, string $label = 'ERROR', false|string|null $icon = null): self
    {
        $resolvedIcon = $this->resolveIcon($icon, Icon::ERROR);

        return $this->render($message, $label, 'boldWhiteBgRed', $resolvedIcon);
    }

    /**
     * Display an error badge with text in "error" style.
     *
     * @param string      $message Badge message
     * @param string      $label   Badge label
     * @param string|null $icon    Optional icon to display before the label
     *                             (null = use default if enabled, false = no icon)
     */
    public function errorFull(string $message, string $label = 'INFO', false|string|null $icon = null): self
    {
        return $this->error($this->color->error($message), $label, $icon);
    }

    /**
     * Display a danger badge (alias for error).
     *
     * @param string      $message Badge message
     * @param string      $label   Badge label
     * @param string|null $icon    Optional icon to display before the label
     *                             (null = use default if enabled, false = no icon)
     */
    public function danger(string $message, string $label = 'DANGER', false|string|null $icon = null): self
    {
        return $this->error($message, $label, $icon);
    }

    /**
     * Display a primary badge.
     *
     * @param string      $message Badge message
     * @param string      $label   Badge label
     * @param string|null $icon    Optional icon to display before the label
     *                             (null = use default if enabled, false = no icon)
     */
    public function primary(string $message, string $label = 'PRIMARY', false|string|null $icon = null): self
    {
        $resolvedIcon = $this->resolveIcon($icon, Icon::PRIMARY);

        return $this->render($message, $label, 'boldWhiteBgBlue', $resolvedIcon);
    }

    /**
     * Display a secondary badge.
     *
     * @param string      $message Badge message
     * @param string      $label   Badge label
     * @param string|null $icon    Optional icon to display before the label
     *                             (null = use default if enabled, false = no icon)
     */
    public function secondary(string $message, string $label = 'SECONDARY', false|string|null $icon = null): self
    {
        $resolvedIcon = $this->resolveIcon($icon, Icon::SECONDARY);

        return $this->render($message, $label, 'boldWhiteBgGray', $resolvedIcon);
    }

    /**
     * Display a dark badge.
     *
     * @param string      $message Badge message
     * @param string      $label   Badge label
     * @param string|null $icon    Optional icon to display before the label
     *                             (null = use default if enabled, false = no icon)
     */
    public function dark(string $message, string $label = 'DARK', false|string|null $icon = null): self
    {
        $resolvedIcon = $this->resolveIcon($icon, Icon::DARK);

        return $this->render($message, $label, 'boldWhiteBgBlack', $resolvedIcon);
    }

    /**
     * Display a light badge.
     *
     * @param string      $message Badge message
     * @param string      $label   Badge label
     * @param string|null $icon    Optional icon to display before the label
     *                             (null = use default if enabled, false = no icon)
     */
    public function light(string $message, string $label = 'LIGHT', false|string|null $icon = null): self
    {
        $resolvedIcon = $this->resolveIcon($icon, Icon::LIGHT);

        return $this->render($message, $label, 'boldBlackBgWhite', $resolvedIcon);
    }

    /**
     * Display an outline badge.
     *
     * @param string      $message Badge message
     * @param string      $label   Badge label
     * @param string      $color   Badge color
     * @param string|null $icon    Optional icon to display before the label
     *                             (null = use default if enabled, false = no icon)
     */
    public function outline(string $message, string $label = 'OUTLINE', string $color = 'blue', false|string|null $icon = null): self
    {
        $method = match ($color) {
            'info'      => 'boldCyan',
            'success'   => 'boldGreen',
            'warning'   => 'boldYellow',
            'error'     => 'boldRed',
            'danger'    => 'boldRed',
            'primary'   => 'boldBlue',
            'secondary' => 'boldGray',
            'dark'      => 'boldBlack',
            'light'     => 'boldWhite',
            default     => 'bold' . ucfirst(strtolower($color)),
        };

        $defaultIcon  = $this->getColorDefaultIcon($color);
        $resolvedIcon = $this->resolveIcon($icon, $defaultIcon);
        $iconPart     = $resolvedIcon ? $resolvedIcon . ' ' : '';

        try {
            $this->writer->{$method}(" {$iconPart}{$label} ");
        } catch (Exception) {
            $this->writer->bold(" {$iconPart}{$label} ");
        }

        $this->writer->write(' ' . $message)->eol();

        return $this;
    }

    /**
     * Display a pill badge (rounded corners).
     *
     * @param string      $message Badge message
     * @param string      $label   Badge label
     * @param string      $color   Badge color
     * @param string|null $icon    Optional icon to display before the label
     *                             (null = use default if enabled, false = no icon)
     */
    public function pill(string $message, string $label = 'PILL', string $color = 'blue', false|string|null $icon = null): self
    {
        $method = match ($color) {
            'info'      => 'boldWhiteBgCyan',
            'success'   => 'boldWhiteBgGreen',
            'warning'   => 'boldWhiteBgYellow',
            'error'     => 'boldWhiteBgRed',
            'danger'    => 'boldWhiteBgRed',
            'primary'   => 'boldWhiteBgBlue',
            'secondary' => 'boldWhiteBgGray',
            'dark'      => 'boldWhiteBgBlack',
            'light'     => 'boldBlackBgWhite',
            default     => 'boldWhiteBgBlue',
        };

        $defaultIcon  = $this->getColorDefaultIcon($color);
        $resolvedIcon = $this->resolveIcon($icon, $defaultIcon);
        $iconPart     = $resolvedIcon ? $resolvedIcon . ' ' : '';

        // Add rounded brackets for pill effect
        $this->writer->{$method}("( {$iconPart}{$label} )");
        $this->writer->write(' ' . $message, true);

        return $this;
    }

    /**
     * Display a badge with custom style.
     *
     * @param string      $message Badge message
     * @param string      $label   Badge label
     * @param string      $style   Writer method to use for styling
     * @param string|null $icon    Optional icon to display before the label
     *                             (null = use default if enabled, false = no icon)
     */
    public function custom(string $message, string $label, string $style, false|string|null $icon = null): self
    {
        $resolvedIcon = $this->resolveIcon($icon, null);

        return $this->render($message, $label, $style, $resolvedIcon);
    }

    /**
     * Render a badge.
     *
     * @param string      $message Badge message
     * @param string      $label   Badge label
     * @param string      $style   Writer method to use for styling
     * @param string|null $icon    Resolved icon (null = no icon)
     */
    private function render(string $message, string $label, string $style, ?string $icon): self
    {
        $iconPart = $icon ? $icon . ' ' : '';

        try {
            $this->writer->{$style}(" {$iconPart}{$label} ");
        } catch (Exception) {
            // Fallback to bold if style method doesn't exist
            $this->writer->bold(" {$iconPart}{$label} ");
        }

        $this->writer->write(' ' . $message, true);

        return $this;
    }

    private function getColorDefaultIcon(string $color): ?string
    {
        return match ($color) {
            'info'      => Icon::INFO,
            'success'   => Icon::SUCCESS,
            'warning'   => Icon::WARNING,
            'error'     => Icon::ERROR,
            'danger'    => Icon::DANGER,
            'primary'   => Icon::PRIMARY,
            'secondary' => Icon::SECONDARY,
            'dark'      => Icon::DARK,
            'light'     => Icon::LIGHT,
            default     => null,
        };
    }
}
