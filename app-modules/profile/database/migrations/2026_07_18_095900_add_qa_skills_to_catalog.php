<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Backfill das skills de QA para bancos que ja executaram a migration
     * inicial. Instancias novas recebem esse catalogo pela migration base.
     *
     * @var array<string, array<string, string>>
     */
    private const array CATALOG = [
        'framework' => [
            'selenium' => 'Selenium',
            'cypress' => 'Cypress',
            'playwright' => 'Playwright',
            'webdriverio' => 'WebdriverIO',
            'testcafe' => 'TestCafe',
            'puppeteer' => 'Puppeteer',
            'appium' => 'Appium',
            'espresso' => 'Espresso',
            'xcuitest' => 'XCUITest',
            'detox' => 'Detox',
            'rest-assured' => 'RestAssured',
            'karate' => 'Karate',
            'supertest' => 'Supertest',
            'cucumber' => 'Cucumber',
            'specflow' => 'SpecFlow',
            'behave' => 'Behave',
            'robot-framework' => 'Robot Framework',
            'junit' => 'JUnit',
            'testng' => 'TestNG',
            'pytest' => 'PyTest',
            'jest' => 'Jest',
            'mocha-chai' => 'Mocha / Chai',
            'nunit' => 'NUnit',
            'xunit' => 'xUnit',
        ],
        'tool' => [
            'postman-newman' => 'Postman / Newman',
            'jmeter' => 'JMeter',
            'k6' => 'K6',
            'gatling' => 'Gatling',
            'locust' => 'Locust',
        ],
    ];

    public function up(): void
    {
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

        DB::table('skills')->upsert(
            $rows,
            ['slug'],
            ['name', 'category', 'updated_at'],
        );
    }

    public function down(): void
    {
        DB::table('skills')
            ->whereIn('slug', array_keys(self::CATALOG['framework']))
            ->orWhereIn('slug', array_keys(self::CATALOG['tool']))
            ->delete();
    }
};
