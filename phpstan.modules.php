<?php

declare(strict_types=1);

$includes = [];

foreach (glob(__DIR__.'/app-modules/*/phpstan.neon') as $file) {
    if (is_file($file)) {
        $includes[] = $file;
    }
}

return ['includes' => $includes];
