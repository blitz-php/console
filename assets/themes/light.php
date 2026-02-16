<?php

use Ahc\Cli\Output\Color;

/**
 * Light theme - optimized for light terminal backgrounds.
 * Uses darker colors for better visibility on white backgrounds.
 */
return [
    // ========================================================================
    // Built-in adhocore/cli styles
    // ========================================================================

    // Help screen styles - darker for light backgrounds
    'help_header'           => ['fg' => Color::GREEN],
    'help_item_even'        => ['fg' => Color::GREEN],
    'help_item_odd'         => ['fg' => Color::GREEN],
    'help_group'            => ['fg' => Color::fg256(28)],
    'help_category'         => ['fg' => Color::YELLOW],
    'help_usage'            => ['fg' => Color::BLACK, 'bg' => Color::WHITE],
    'help_description_even' => ['fg' => Color::BLACK, 'bg' => Color::WHITE],
    'help_description_odd'  => ['fg' => Color::BLACK, 'bg' => Color::WHITE],
    'help_summary'          => ['fg' => Color::BLACK, 'bg' => Color::WHITE],
    'help_example'          => ['fg' => Color::BLACK, 'bg' => Color::WHITE],
    'help_text'             => ['fg' => Color::BLACK, 'bg' => Color::WHITE],
    'help_footer'           => ['fg' => Color::BLACK, 'bg' => Color::WHITE],

    // Input/Output styles
    'answer'                => ['fg' => Color::BLUE, 'bold' => 1],
    'choice'                => ['fg' => Color::GREEN, 'bold' => 1],
    'comment'               => ['fg' => Color::YELLOW, 'bold' => 1],
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

    // Extended colors - darkened for light backgrounds
    'magenta'               => ['fg' => Color::fg256(162)],
    'indigo'                => ['fg' => Color::fg256(55)],
    'purple'                => ['fg' => Color::fg256(92)],
    'orange'                => ['fg' => Color::fg256(166)],
    'pink'                  => ['fg' => Color::fg256(168)],
    'brown'                 => ['fg' => Color::fg256(94)],
];
