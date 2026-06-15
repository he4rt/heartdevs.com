<?php

declare(strict_types=1);

use MarvinLabs\DiscordLogger\Converters\RichRecordConverter;

return [

    /*
     * The author of the log messages. You can set both to null to keep the Webhook author set in Discord
     */
    'from' => [
        'name' => env('APP_NAME', 'Laravel Logger'),
        'avatar_url' => null,
    ],

    /**
     * The converter to use to turn a log record into a discord message
     *
     * Bundled converters:
     * - \MarvinLabs\DiscordLogger\Converters\SimpleRecordConverter::class
     * - \MarvinLabs\DiscordLogger\Converters\RichRecordConverter::class
     */
    'converter' => RichRecordConverter::class,

    /**
     * If enabled, stacktraces will be attached as files. If not, stacktraces will be directly printed out in the
     * message.
     *
     * Valid values are:
     *
     * - 'smart': when stacktrace is less than 2000 characters, it is inlined with the message, else attached as file
     * - 'file': stacktrace is always attached as file
     * - 'inline': stacktrace is always inlined with the message, truncated if necessary
     */
    'stacktrace' => 'file',

    /*
     * A set of colors to associate to the different log levels when using the `RichRecordConverter`
     */
    'colors' => [
        'DEBUG' => 0x60_7D_8B,
        'INFO' => 0x4C_AF_50,
        'NOTICE' => 0x21_96_F3,
        'WARNING' => 0xFF_98_00,
        'ERROR' => 0xF4_43_36,
        'CRITICAL' => 0xE9_1E_63,
        'ALERT' => 0x67_3A_B7,
        'EMERGENCY' => 0x9C_27_B0,
    ],

    /*
     * A set of emojis to associate to the different log levels. Set to null to disable an emoji for a given level
     */
    'emojis' => [
        'DEBUG' => null,
        'INFO' => null,
        'NOTICE' => null,
        'WARNING' => null,
        'ERROR' => null,
        'CRITICAL' => null,
        'ALERT' => null,
        'EMERGENCY' => null,
    ],
];
