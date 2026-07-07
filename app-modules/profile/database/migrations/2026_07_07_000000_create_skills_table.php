<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Catálogo inicial de skills. Valores literais de propósito — a migration é
     * histórica e não deve depender do enum SkillCategory nem do model Skill.
     *
     * @var array<string, array<string, string>>
     */
    private const array CATALOG = [
        'language' => [
            'php' => 'PHP',
            'javascript' => 'JavaScript',
            'typescript' => 'TypeScript',
            'python' => 'Python',
            'go' => 'Go',
        ],
        'framework' => [
            'laravel' => 'Laravel',
            'react' => 'React',
            'vue' => 'Vue.js',
            'nextjs' => 'Next.js',
        ],
        'database' => [
            'postgresql' => 'PostgreSQL',
            'mysql' => 'MySQL',
            'redis' => 'Redis',
            'mongodb' => 'MongoDB',
        ],
        'tool' => [
            'docker' => 'Docker',
            'git' => 'Git',
            'kubernetes' => 'Kubernetes',
        ],
        'soft' => [
            'leadership' => 'Liderança',
            'communication' => 'Comunicação',
            'teamwork' => 'Trabalho em equipe',
        ],
    ];

    public function up(): void
    {
        Schema::create('skills', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('category', 30);
            $table->string('icon')->nullable();
            $table->timestampsTz();

            $table->index('category');
        });

        $now = now();
        $rows = [];

        foreach (self::CATALOG as $category => $skills) {
            foreach ($skills as $slug => $name) {
                $rows[] = [
                    'id' => Str::orderedUuid()->toString(),
                    'slug' => $slug,
                    'name' => $name,
                    'category' => $category,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('skills')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('skills');
    }
};
