<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Squads\Actions\AssignCaptain;
use He4rt\Squads\Actions\MarkExMember;
use He4rt\Squads\Models\Squad;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 4).'/vendor/autoload.php';

$app = require dirname(__DIR__, 4).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

/** @var array{action: string, actor_id: string, squad_id: string, subject_id: string, admin_username: string} $payload */
$payload = json_decode($argv[1] ?? '', associative: true, flags: JSON_THROW_ON_ERROR);

config(['he4rt.admins' => $payload['admin_username']]);

fwrite(STDOUT, json_encode([
    'pid' => (int) DB::selectOne('select pg_backend_pid() as pid')->pid,
], JSON_THROW_ON_ERROR).PHP_EOL);
fflush(STDOUT);

try {
    $actor = User::query()->findOrFail($payload['actor_id']);
    $squad = Squad::query()->findOrFail($payload['squad_id']);
    $subject = User::query()->findOrFail($payload['subject_id']);

    match ($payload['action']) {
        'assign' => resolve(AssignCaptain::class)->handle($actor, $squad, $subject),
        'mark_ex' => resolve(MarkExMember::class)->handle($actor, $squad, $subject),
        default => throw new InvalidArgumentException('Unsupported action: '.$payload['action']),
    };

    $result = ['ok' => true];
} catch (Throwable $throwable) {
    $result = [
        'ok' => false,
        'exception' => $throwable::class,
        'message' => $throwable->getMessage(),
    ];
}

fwrite(STDOUT, json_encode($result, JSON_THROW_ON_ERROR).PHP_EOL);
