<?php

declare(strict_types=1);

use He4rt\Events\Models\EventModel;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_agenda', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Tenant::class)->constrained('tenants');
            $table->foreignIdFor(EventModel::class, 'event_id')->constrained('events');
            $table->string('schedulable_type');
            $table->string('schedulable_id');
            $table->string('starting_at');
            $table->string('ending_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['schedulable_type', 'schedulable_id'], 'schedulable_index');
        });
    }
};
