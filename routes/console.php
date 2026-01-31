<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;
use Laravel\Telescope\Console\ClearCommand;

Schedule::command(ClearCommand::class)->daily()->timezone(config('app.timezone'));
Schedule::command('cloudflare:reload')->daily();
