<?php

use Ahc\Cli\Output\Color;

/**
 * Dark theme - optimized for dark terminal backgrounds.
 * High contrast, easy on the eyes for long sessions.
 */
return [
    // ========================================================================
    // Built-in adhocore/cli styles
    // ========================================================================

    // Help screen styles - brighter for dark backgrounds
    'help_header'           => ['fg' => Color::GREEN],
    'help_item_even'        => ['fg' => Color::GREEN],
    'help_item_odd'         => ['fg' => Color::GREEN],
    'help_group'            => ['fg' => Color::fg256(49), 'bold' => 1],
    'help_category'         => ['fg' => Color::YELLOW],
    'help_usage'            => ['fg' => Color::WHITE, 'bold' => 1],
    'help_description_even' => ['fg' => Color::WHITE],
    'help_description_odd'  => ['fg' => Color::WHITE],
    'help_summary'          => ['fg' => Color::WHITE],
    'help_example'          => ['fg' => Color::GRAY],
    'help_text'             => ['fg' => Color::WHITE],
    'help_footer'           => ['fg' => Color::GRAY],

    // Input/Output styles
    'answer'                => ['fg' => Color::CYAN, 'bold' => 1],
    'choice'                => ['fg' => Color::GREEN, 'bold' => 1],
    'comment'               => ['fg' => Color::YELLOW],
    'error'                 => ['fg' => Color::RED, 'bold' => 1],
    'info'                  => ['fg' => Color::BLUE],
    'logo'                  => ['fg' => Color::PURPLE],
    'ok'                    => ['fg' => Color::GREEN],
    'question'              => ['fg' => Color::YELLOW],
    'version'               => ['fg' => Color::GREEN],
    'warn'                  => ['fg' => Color::YELLOW],

    // ========================================================================
    // BlitzPHP custom styles
    // ========================================================================

    // Extended colors - brighter for dark backgrounds
    'magenta'               => ['fg' => Color::fg256(201)],
    'indigo'                => ['fg' => Color::fg256(63)],
    'purple'                => ['fg' => Color::fg256(135)],
    'orange'                => ['fg' => Color::fg256(214)],
    'pink'                  => ['fg' => Color::fg256(212)],
    'brown'                 => ['fg' => Color::fg256(94)],
];
