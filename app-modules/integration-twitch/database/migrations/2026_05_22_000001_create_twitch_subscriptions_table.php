<?php

declare(strict_types=1);

use He4rt\IntegrationTwitch\Enums\TwitchSubscriptionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('twitch_subscriptions', static function (Blueprint $table): void {
            $table->id();
            $table->string('subscription_id')->unique();
            $table->string('type');
            $table->string('status')->comment(TwitchSubscriptionStatus::stringifyCases());
            $table->string('broadcaster_user_id');
            $table->jsonb('condition');
            $table->string('transport')->default('webhook');
            $table->string('callback_url')->nullable();
            $table->integer('cost')->default(0);
            $table->string('version')->default('1');
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->timestampsTz();

            $table->index('type');
            $table->index('broadcaster_user_id');
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('twitch_subscriptions');
    }
};
