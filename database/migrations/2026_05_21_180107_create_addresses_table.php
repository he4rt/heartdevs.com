<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuidMorphs('addressable'); // addressable_type && addressable_id

            $table->string('country', 4)->nullable();
            $table->string('state', 4)->nullable();
            $table->string('city')->nullable();
            $table->string('zip_code')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
