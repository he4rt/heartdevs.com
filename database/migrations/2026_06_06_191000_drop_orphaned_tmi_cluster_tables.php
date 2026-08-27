<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('tmi_cluster_channels');
        Schema::dropIfExists('tmi_cluster_supervisor_processes');
        Schema::dropIfExists('tmi_cluster_supervisors');
    }
};
