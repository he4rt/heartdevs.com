<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('telescope:prune', ['--hours' => 36, '--keep-exceptions'])
    ->when(fn () => config()->boolean('telescope.enabled'))
    ->daily();

Schedule::command('cloudflare:reload')
    ->when(fn () => config()->boolean('laravelcloudflare.enabled'))
    ->twiceMonthly();

Schedule::command('backup:run', ['--only-db'])
    ->when(fn () => config()->boolean('backup.enabled'))
    ->everyThirtyMinutes();

Schedule::command('backup:clean')
    ->when(fn () => config()->boolean('backup.enabled'))
    ->hourly();

Schedule::command('backup:monitor')
    ->when(fn () => config()->boolean('backup.enabled'))
    ->everyThreeHours();
