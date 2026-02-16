<?php

use Ahc\Cli\Output\Color;

/**
 * Default theme for BlitzPHP Console.
 * This theme matches the original adhocore/cli styling.
 */
return [
    // ========================================================================
    // Built-in adhocore/cli styles
    // ========================================================================

    // Help screen styles
    'help_header'           => ['fg' => Color::GREEN],
    'help_item_even'        => ['fg' => Color::GREEN],
    'help_item_odd'         => ['fg' => Color::GREEN],
    'help_group'            => ['fg' => Color::fg256(149)],
    'help_category'         => ['fg' => Color::YELLOW],
    'help_usage'            => ['fg' => Color::WHITE],
    'help_description_even' => ['fg' => Color::WHITE],
    'help_description_odd'  => ['fg' => Color::WHITE],
    'help_summary'          => ['fg' => Color::WHITE],
    'help_example'          => ['fg' => Color::GRAY],
    'help_text'             => ['fg' => Color::WHITE],
    'help_footer'           => ['fg' => Color::GRAY],

    // Input/Output styles
    'answer'                => ['fg' => Color::CYAN],
    'choice'                => ['fg' => Color::GREEN],
    'comment'               => ['fg' => Color::YELLOW],
    'error'                 => ['fg' => Color::RED],
    'info'                  => ['fg' => Color::BLUE],
    'logo'                  => ['fg' => Color::PURPLE],
    'ok'                    => ['fg' => Color::GREEN],
    'question'              => ['fg' => Color::YELLOW],
    'version'               => ['fg' => Color::GREEN],
    'warn'                  => ['fg' => Color::YELLOW],

    // ========================================================================
    // BlitzPHP custom styles
    // ========================================================================

    // Text styles
    'underline'             => ['bold' => 4],
    'italic'                => ['bold' => 3],
    'strike'                => ['bold' => 9],

    // Extended colors
    'magenta'               => ['fg' => Color::fg256(201)],
    'indigo'                => ['fg' => Color::fg256(54)],
    'purple'                => ['fg' => Color::fg256(129)],
    'orange'                => ['fg' => Color::fg256(208)],
    'pink'                  => ['fg' => Color::fg256(205)],
    'brown'                 => ['fg' => Color::fg256(130)],
];
