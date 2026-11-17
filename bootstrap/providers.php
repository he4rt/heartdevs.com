<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\TypeScriptTransformerServiceProvider;

return [
    AppServiceProvider::class,
    TypeScriptTransformerServiceProvider::class,
];
