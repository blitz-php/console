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

use Ahc\Cli\Helper\InflectsString;
use Ahc\Cli\Output\Writer;
use Dimtrovich\Console\Icon;

/**
 * Alert component for console output.
 */
class Alert
{
    use IconTrait;
    use SingletonTrait;
    use InflectsString;

    /**
     * Writer instance.
     */
    private Writer $writer;

    /**
     * Create a new alert instance.
     *
     * @param Writer $writer Writer instance
     */
    public function __construct(Writer $writer)
    {
        $this->writer = $writer;
    }

    /**
     * Display an info alert.
     *
     * @param string      $message Alert message
     * @param string|null $title   Alert title
     * @param string|null $icon    Optional icon to display before the title
     *                             (null = use default if enabled, false = no icon)
     */
    public function info(string $message, ?string $title = null, false|string|null $icon = null): self
    {
        $resolvedIcon = $this->resolveIcon($icon, Icon::INFO);

        return $this->render($message, 'info', $title ?? 'INFO', $resolvedIcon);
    }

    /**
     * Display a success alert.
     *
     * @param string      $message Alert message
     * @param string|null $title   Alert title
     * @param string|null $icon    Optional icon to display before the title
     *                             (null = use default if enabled, false = no icon)
     */
    public function success(string $message, ?string $title = null, false|string|null $icon = null): self
    {
        $resolvedIcon = $this->resolveIcon($icon, Icon::SUCCESS);

        return $this->render($message, 'success', $title ?? 'SUCCESS', $resolvedIcon);
    }

    /**
     * Display a warning alert.
     *
     * @param string      $message Alert message
     * @param string|null $title   Alert title
     * @param string|null $icon    Optional icon to display before the title
     *                             (null = use default if enabled, false = no icon)
     */
    public function warning(string $message, ?string $title = null, false|string|null $icon = null): self
    {
        $resolvedIcon = $this->resolveIcon($icon, Icon::WARNING);

        return $this->render($message, 'warning', $title ?? 'WARNING', $resolvedIcon);
    }

    /**
     * Display an error alert.
     *
     * @param string      $message Alert message
     * @param string|null $title   Alert title
     * @param string|null $icon    Optional icon to display before the title
     *                             (null = use default if enabled, false = no icon)
     */
    public function error(string $message, ?string $title = null, false|string|null $icon = null): self
    {
        $resolvedIcon = $this->resolveIcon($icon, Icon::ERROR);

        return $this->render($message, 'error', $title ?? 'ERROR', $resolvedIcon);
    }

    /**
     * Display a danger alert (alias for error).
     *
     * @param string      $message Alert message
     * @param string|null $title   Alert title
     * @param string|null $icon    Optional icon to display before the title
     *                             (null = use default if enabled, false = no icon)
     */
    public function danger(string $message, ?string $title = null, false|string|null $icon = null): self
    {
        return $this->error($message, $title, $icon);
    }

    /**
     * Display a primary alert.
     *
     * @param string      $message Alert message
     * @param string|null $title   Alert title
     * @param string|null $icon    Optional icon to display before the title
     *                             (null = use default if enabled, false = no icon)
     */
    public function primary(string $message, ?string $title = null, false|string|null $icon = null): self
    {
        $resolvedIcon = $this->resolveIcon($icon, Icon::PRIMARY);

        return $this->render($message, 'primary', $title ?? 'ALERT', $resolvedIcon);
    }

    /**
     * Display a secondary alert.
     *
     * @param string      $message Alert message
     * @param string|null $title   Alert title
     * @param string|null $icon    Optional icon to display before the title
     *                             (null = use default if enabled, false = no icon)
     */
    public function secondary(string $message, ?string $title = null, false|string|null $icon = null): self
    {
        $resolvedIcon = $this->resolveIcon($icon, Icon::SECONDARY);

        return $this->render($message, 'secondary', $title ?? 'NOTE', $resolvedIcon);
    }

    /**
     * Display a dark alert.
     *
     * @param string      $message Alert message
     * @param string|null $title   Alert title
     * @param string|null $icon    Optional icon to display before the title
     *                             (null = use default if enabled, false = no icon)
     */
    public function dark(string $message, ?string $title = null, false|string|null $icon = null): self
    {
        $resolvedIcon = $this->resolveIcon($icon, Icon::DARK);

        return $this->render($message, 'dark', $title ?? 'ALERT', $resolvedIcon);
    }

    /**
     * Display a light alert.
     *
     * @param string      $message Alert message
     * @param string|null $title   Alert title
     * @param string|null $icon    Optional icon to display before the title
     *                             (null = use default if enabled, false = no icon)
     */
    public function light(string $message, ?string $title = null, false|string|null $icon = null): self
    {
        $resolvedIcon = $this->resolveIcon($icon, Icon::LIGHT);

        return $this->render($message, 'light', $title ?? 'NOTE', $resolvedIcon);
    }

    /**
     * Display a custom alert.
     *
     * @param string      $message Alert message
     * @param string      $type    Alert type for color scheme
     * @param string      $title   Alert title
     * @param string|null $icon    Optional icon to display before the title
     *                             (null = use default if enabled, false = no icon)
     */
    public function custom(string $message, string $type, string $title, false|string|null $icon = null): self
    {
        $resolvedIcon = $this->resolveIcon($icon, null);

        return $this->render($message, $type, $title, $resolvedIcon);
    }

    /**
     * Render the alert.
     *
     * @param string      $message Alert message
     * @param string      $type    Alert type
     * @param string      $title   Alert title
     * @param string|null $icon    Resolved icon (null = no icon)
     */
    private function render(string $message, string $type, string $title, ?string $icon): self
    {
        $this->writer->eol();

        // Calculate the total le,gth of border
        $iconLength  = $icon ? 2 : 0; // Icon + space
        $titleLength = $this->strwidth($title) + $iconLength;
        $maxLength   = max($this->strwidth($message), $titleLength + 2) + 12;

        // Top border
        $this->renderBorder($maxLength, $type);

        // Title line with icon
        $this->renderTitle($title, $type, $icon, $maxLength);

        // Message line
        $this->renderMessage($message, $type, $maxLength);

        // Bottom border
        $this->renderBorder($maxLength, $type);

        $this->writer->eol();

        return $this;
    }

    /**
     * Render alert border.
     *
     * @param int    $maxLength Maximum length of the border
     * @param string $type      Alert type
     */
    private function renderBorder(int $maxLength, string $type): void
    {
        $border = str_repeat('*', $maxLength);

        $this->writer->colors('<' . $this->getBorderColor($type) . '>' . $border . '</end>')->eol();
    }

    /**
     * Render alert title with optional icon.
     *
     * @param string      $title Alert title
     * @param string      $type  Alert type
     * @param string|null $icon  Optional icon
     */
    private function renderTitle(string $title, string $type, ?string $icon, int $maxLength): void
    {
        $iconPart     = $icon ? $icon . '  ' : '';
        $displayTitle = $iconPart . $title;

        // Calculate the left part of the border (before the title)
        $rightBorderLength = $icon ? 1 : -1;
        $titleWithSpaces   = '  ' . $displayTitle . '  ';
        $titleTotalLength  = $this->strwidth($titleWithSpaces);

        // Calculate how many asterisks after the title
        $remainingLength = $maxLength - $titleTotalLength + $rightBorderLength;

        if ($remainingLength >= 2) {
            // Construction of the top border: "*  TITLE  ********"
            $topBorder = '*  ' . $displayTitle . '  ' . str_repeat('*', $remainingLength);
            $this->writer->colors('<' . $this->getTitleColor($type) . '>' . $topBorder . '</end>')->eol();
        } else {
            // If there is not enough space, use the traditional method.
            $border = str_repeat('*', $maxLength);
            $this->writer->colors('<' . $this->getBorderColor($type) . '>' . $border . '</end>')->eol();

            // Then display the title on the next line
            $formattedTitle = str_pad('*  ' . $displayTitle . '  *', $maxLength, ' ', STR_PAD_RIGHT);
            $this->writer->colors('<' . $this->getTitleColor($type) . '>' . $formattedTitle . '</end>')->eol();
        }
    }

    /**
     * Render alert message.
     *
     * @param string $message Alert message
     * @param string $type    Alert type
     */
    private function renderMessage(string $message, string $type, int $maxLength): void
    {
        $lines = explode("\n", wordwrap($message, 60, "\n", true));

        foreach ($lines as $line) {
            // Calculate the padding needed for the message to be aligned with the border
            $paddedLine    = '*  ' . $line;
            $paddingNeeded = $maxLength - $this->strwidth($paddedLine);
            $paddedLine .= str_repeat(' ', max(0, $paddingNeeded)) . ' *';

            $this->writer->colors('<' . $this->getMessageColor($type) . '>' . $paddedLine . '</end>')->eol();
        }
    }

    /**
     * Get border color for alert type.
     *
     * @param string $type Alert type
     *
     * @return string Color name
     */
    private function getBorderColor(string $type): string
    {
        return match ($type) {
            'info'      => 'cyan',
            'success'   => 'green',
            'warning'   => 'yellow',
            'error'     => 'red',
            'danger'    => 'red',
            'primary'   => 'blue',
            'secondary' => 'gray',
            'dark'      => 'black',
            'light'     => 'white',
            default     => 'white',
        };
    }

    /**
     * Get title color for alert type.
     *
     * @param string $type Alert type
     *
     * @return string Color name
     */
    private function getTitleColor(string $type): string
    {
        return match ($type) {
            'info'      => 'boldCyan',
            'success'   => 'boldGreen',
            'warning'   => 'boldYellow',
            'error'     => 'boldRed',
            'danger'    => 'boldRed',
            'primary'   => 'boldBlue',
            'secondary' => 'boldGray',
            'dark'      => 'boldWhite',
            'light'     => 'boldBlack',
            default     => 'boldWhite',
        };
    }

    /**
     * Get message color for alert type.
     *
     * @param string $type Alert type
     *
     * @return string Color name
     */
    private function getMessageColor(string $type): string
    {
        return match ($type) {
            'info'      => 'cyan',
            'success'   => 'green',
            'warning'   => 'yellow',
            'error'     => 'red',
            'danger'    => 'red',
            'primary'   => 'blue',
            'secondary' => 'gray',
            'dark'      => 'white',
            'light'     => 'black',
            default     => 'white',
        };
    }
}
