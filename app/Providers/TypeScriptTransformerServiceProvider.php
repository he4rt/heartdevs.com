<?php

declare(strict_types=1);

namespace App\Providers;

use Spatie\LaravelTypeScriptTransformer\LaravelData\LaravelDataTypeScriptTransformerExtension;
use Spatie\LaravelTypeScriptTransformer\TypeScriptTransformerApplicationServiceProvider;
use Spatie\TypeScriptTransformer\Transformers\EnumTransformer;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfigFactory;
use Spatie\TypeScriptTransformer\Writers\GlobalNamespaceWriter;

final class TypeScriptTransformerServiceProvider extends TypeScriptTransformerApplicationServiceProvider
{
    protected function configure(TypeScriptTransformerConfigFactory $config): void
    {
        $config
            ->outputDirectory(resource_path('js/generated'))
            ->extension(new LaravelDataTypeScriptTransformerExtension)
            ->transformer(EnumTransformer::class)
            ->transformDirectories(app_path('Data'), app_path('Enums'), ...glob(base_path('app-modules/*/src')) ?: [])
            ->writer(new GlobalNamespaceWriter('types.d.ts'));
    }
}
