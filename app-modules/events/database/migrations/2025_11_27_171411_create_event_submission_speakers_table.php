<?php

declare(strict_types=1);

use He4rt\Events\Models\EventSubmission;
use He4rt\User\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_submission_speakers', function (Blueprint $table): void {
            $table->foreignIdFor(EventSubmission::class, 'submission_id')->constrained('events_talks');
            $table->foreignIdFor(User::class)->constrained('users');
        });
    }
};
