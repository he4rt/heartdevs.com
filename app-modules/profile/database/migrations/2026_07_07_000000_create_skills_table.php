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
            'java' => 'Java',
            'csharp' => 'C#',
            'cpp' => 'C++',
            'c' => 'C',
            'ruby' => 'Ruby',
            'rust' => 'Rust',
            'kotlin' => 'Kotlin',
            'swift' => 'Swift',
            'scala' => 'Scala',
            'elixir' => 'Elixir',
            'dart' => 'Dart',
            'lua' => 'Lua',
            'sql' => 'SQL',
        ],
        'framework' => [
            'laravel' => 'Laravel',
            'symfony' => 'Symfony',
            'react' => 'React',
            'vue' => 'Vue.js',
            'angular' => 'Angular',
            'svelte' => 'Svelte',
            'nextjs' => 'Next.js',
            'nuxt' => 'Nuxt',
            'astro' => 'Astro',
            'express' => 'Express',
            'nestjs' => 'NestJS',
            'django' => 'Django',
            'flask' => 'Flask',
            'fastapi' => 'FastAPI',
            'spring-boot' => 'Spring Boot',
            'rails' => 'Ruby on Rails',
            'dotnet' => '.NET',
            'phoenix' => 'Phoenix',
            'react-native' => 'React Native',
            'flutter' => 'Flutter',
            'tailwindcss' => 'Tailwind CSS',
            'livewire' => 'Livewire',
        ],
        'database' => [
            'postgresql' => 'PostgreSQL',
            'mysql' => 'MySQL',
            'mariadb' => 'MariaDB',
            'sqlite' => 'SQLite',
            'sqlserver' => 'SQL Server',
            'oracle' => 'Oracle Database',
            'redis' => 'Redis',
            'mongodb' => 'MongoDB',
            'cassandra' => 'Cassandra',
            'scylladb' => 'ScyllaDB',
            'elasticsearch' => 'Elasticsearch',
            'dynamodb' => 'DynamoDB',
            'clickhouse' => 'ClickHouse',
            'neo4j' => 'Neo4j',
            'firebase' => 'Firebase',
        ],
        'tool' => [
            'docker' => 'Docker',
            'kubernetes' => 'Kubernetes',
            'git' => 'Git',
            'linux' => 'Linux',
            'nginx' => 'Nginx',
            'terraform' => 'Terraform',
            'ansible' => 'Ansible',
            'jenkins' => 'Jenkins',
            'github-actions' => 'GitHub Actions',
            'gitlab-ci' => 'GitLab CI/CD',
            'aws' => 'AWS',
            'gcp' => 'Google Cloud',
            'azure' => 'Azure',
            'kafka' => 'Apache Kafka',
            'rabbitmq' => 'RabbitMQ',
            'grafana' => 'Grafana',
            'prometheus' => 'Prometheus',
            'figma' => 'Figma',
        ],
        'soft' => [
            'leadership' => 'Liderança',
            'communication' => 'Comunicação',
            'teamwork' => 'Trabalho em equipe',
            'problem-solving' => 'Resolução de problemas',
            'adaptability' => 'Adaptabilidade',
            'time-management' => 'Gestão de tempo',
            'critical-thinking' => 'Pensamento crítico',
            'mentoring' => 'Mentoria',
            'creativity' => 'Criatividade',
            'emotional-intelligence' => 'Inteligência emocional',
            'conflict-resolution' => 'Resolução de conflitos',
            'public-speaking' => 'Oratória',
            'proactivity' => 'Proatividade',
            'ownership' => 'Ownership',
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
                    'id' => Str::uuid7()->toString(),
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
