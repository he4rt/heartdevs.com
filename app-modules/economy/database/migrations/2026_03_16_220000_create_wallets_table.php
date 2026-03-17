<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuidMorphs('owner');
            $table->string('currency');
            $table->integer('balance')->default(0);
            $table->timestamps();

            $table->unique(['owner_type', 'owner_id', 'currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
