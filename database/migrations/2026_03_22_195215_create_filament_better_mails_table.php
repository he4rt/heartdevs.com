<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mails', static function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->nullable()->index();
            $table->string('mail_class')->nullable()->index();
            $table->string('transport');
            $table->string('mailer');
            $table->string('stream_id')->nullable();

            $table->string('subject')->nullable();
            $table->json('from')->nullable();
            $table->json('reply_to')->nullable();
            $table->json('to')->nullable();
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->text('html')->nullable();
            $table->text('text')->nullable();

            $table->unsignedBigInteger('opens')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->json('tags')->nullable();

            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('resent_at')->nullable();
            $table->timestampTz('accepted_at')->nullable();
            $table->timestampTz('delivered_at')->nullable();
            $table->timestampTz('last_opened_at')->nullable();
            $table->timestampTz('last_clicked_at')->nullable();
            $table->timestampTz('complained_at')->nullable();
            $table->timestampTz('soft_bounced_at')->nullable();
            $table->timestampTz('hard_bounced_at')->nullable();
            $table->timestampTz('unsubscribed_at')->nullable();
            $table->timestampTz('scheduled_at')->nullable();
            $table->timestampTz('suppressed_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('mail_events', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mail_id')
                ->constrained('mails')
                ->cascadeOnDelete();
            $table->string('type');
            $table->string('ip_address')->nullable();
            $table->string('hostname')->nullable();
            $table->string('platform')->nullable();
            $table->string('os')->nullable();
            $table->string('browser')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('city')->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('link')->nullable();
            $table->string('tag')->nullable();
            $table->json('payload')->nullable();
            $table->timestampTz('occurred_at')->nullable();
            $table->timestampTz('unsuppressed_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('mailables', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('better_email_id')
                ->constrained('mails')
                ->cascadeOnDelete();
            $table->morphs('mailable');
        });

        Schema::create('mail_attachments', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mail_id')
                ->constrained('mails')
                ->cascadeOnDelete();
            $table->string('disk');
            $table->string('uuid');
            $table->string('filename');
            $table->string('mime');
            $table->boolean('inline');
            $table->bigInteger('size');
            $table->timestampsTz();
        });
    }
};
