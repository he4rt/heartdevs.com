<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;

return new class extends Migration
{
    public function up(): void
    {
        $modelClass = config('activitylog.activity_model', Activity::class);

        throw_if(!is_string($modelClass) || !is_a($modelClass, Activity::class, allow_string: true), InvalidArgumentException::class, 'activitylog.activity_model must name a subclass of '.Activity::class);

        $activity = new $modelClass;
        $schema = Schema::connection($activity->getConnectionName());
        $tableName = $activity->getTable();

        if (!$schema->hasTable($tableName)) {
            return;
        }

        $schema->table($tableName, function (Blueprint $table) use ($schema, $tableName): void {
            if (!$schema->hasColumn($tableName, 'risk_score')) {
                $table->unsignedTinyInteger('risk_score')->nullable()->index('activity_log_risk_score_index');
            }

            if (!$schema->hasColumn($tableName, 'risk_level')) {
                $table->string('risk_level', 16)->nullable()->index('activity_log_risk_level_index');
            }

            if (!$schema->hasColumn($tableName, 'retention_hold')) {
                $table->boolean('retention_hold')->default(value: false)->index('activity_log_retention_hold_index');
            }

            if (!$schema->hasColumn($tableName, 'integrity_hash')) {
                $table->char('integrity_hash', 64)->nullable()->index('activity_log_integrity_hash_index');
            }

            if (!$schema->hasColumn($tableName, 'request_id')) {
                $table->string('request_id', 100)->nullable();
            }

            if (!$schema->hasColumn($tableName, 'ip_address')) {
                $table->string('ip_address', 45)->nullable();
            }
        });

        $schema->table($tableName, function (Blueprint $table) use ($schema, $tableName): void {
            foreach ($this->indexes() as $name => $columns) {
                if (!$schema->hasIndex($tableName, $name)) {
                    $table->index($columns, $name);
                }
            }
        });
    }

    /**
     * @return array<string, list<string>>
     */
    private function indexes(): array
    {
        return [
            'activity_log_log_created_index' => ['log_name', 'created_at'],
            'activity_log_event_created_index' => ['event', 'created_at'],
            'activity_log_subject_created_index' => ['subject_type', 'subject_id', 'created_at'],
            'activity_log_causer_created_index' => ['causer_type', 'causer_id', 'created_at'],
            'activity_log_risk_created_index' => ['risk_level', 'created_at'],
            'activity_log_request_created_index' => ['request_id', 'created_at'],
            'activity_log_ip_created_index' => ['ip_address', 'created_at'],
        ];
    }
};
