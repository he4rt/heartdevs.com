<?php

declare(strict_types=1);

$includes = [];

$excluded = [
    __DIR__.'/app-modules/bot-discord/phpstan.neon',
];

foreach (glob(__DIR__.'/app-modules/*/phpstan.neon') as $file) {
    if (is_file($file) && !in_array($file, $excluded, true)) {
        $includes[] = $file;
    }
}

return ['includes' => $includes];
