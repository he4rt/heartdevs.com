<?php

declare(strict_types=1);

if (! function_exists('modules_path')) {
    /**
     * Get the path to the modules' folder.
     *
     * @see https://github.com/InterNACHI/modular/pull/99
     */
    function modules_path(string $path = ''): string
    {
        $directory_name = config('app-modules.modules_directory', 'app-modules');
        $path = base_path($directory_name.DIRECTORY_SEPARATOR.mb_ltrim($path, '/\\'));

        return str_replace('\\', '/', mb_rtrim($path, '/\\'));
    }
}
