<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('event_submission_speakers');
        Schema::dropIfExists('events_agenda');
        Schema::dropIfExists('events_sponsors');
        Schema::dropIfExists('events_attendees');
        Schema::dropIfExists('events_talks');
        Schema::dropIfExists('sponsors');
        Schema::dropIfExists('events');
    }

    public function down(): void
    {
        // Irreversible: removes legacy events module tables replaced by the new schema.
    }
};
