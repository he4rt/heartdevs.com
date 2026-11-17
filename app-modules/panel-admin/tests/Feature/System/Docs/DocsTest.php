<?php

declare(strict_types=1);

use App\Enums\Language;
use He4rt\Identity\Users\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function (): void {
    config(['panel-admin.docs.path' => dirname(__DIR__, 3).'/Fixtures/docs']);
});

it('opens the first page of the tree', function (): void {
    actingAs(User::factory()->superAdmin()->create(['locale' => Language::English->value]));

    get(route('panel-admin::docs.index'))->assertRedirect(route('panel-admin::docs.show', ['path' => 'intro']));
});

it('answers 404 when the docs have no page', function (): void {
    config(['panel-admin.docs.path' => sys_get_temp_dir().'/he4rt-docs-missing']);
    actingAs(User::factory()->superAdmin()->create(['locale' => Language::English->value]));

    $this->withExceptionHandling()->get(route('panel-admin::docs.index'))->assertNotFound();
});

it('renders a page with the tree in front matter order', function (): void {
    actingAs(User::factory()->superAdmin()->create(['locale' => Language::English->value]));

    get(route('panel-admin::docs.show', ['path' => 'intro']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('docs/show')
            ->where('page.title', 'Intro')
            ->where('page.isFallback', expected: false)
            ->where('page.html', fn (string $html): bool => str_contains($html, '<strong>world</strong>') && str_contains($html, '<table>') && !str_contains($html, '<script>'))
            ->where('tree.0.path', 'intro')
            ->where('tree.1.path', 'guides')
            ->where('tree.1.children.0.title', 'Second guide')
            ->where('tree.1.children.1.title', 'First guide'));
});

it('lists the pages of a group under its text', function (): void {
    actingAs(User::factory()->superAdmin()->create(['locale' => Language::English->value]));

    get(route('panel-admin::docs.show', ['path' => 'guides']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('page.children', 2)
            ->where('page.children.0.path', 'guides/second'));
});

it('shows the user locale and falls back to english for a missing page', function (): void {
    actingAs(User::factory()->superAdmin()->create(['locale' => Language::BrazilianPortuguese->value]));

    get(route('panel-admin::docs.show', ['path' => 'intro']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('page.title', 'Introdução')
            ->where('page.locale', 'pt_BR')
            ->where('tree.1.title', 'Guias')
            ->where('tree.1.children.0.title', 'Second guide'));

    get(route('panel-admin::docs.show', ['path' => 'guides/first']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('page.title', 'First guide')
            ->where('page.locale', 'en')
            ->where('page.isFallback', expected: true));
});

it('answers 404 for a missing page or a path outside the docs', function (string $path): void {
    actingAs(User::factory()->superAdmin()->create(['locale' => Language::English->value]));

    $this->withExceptionHandling()->get('/control/docs/'.$path)->assertNotFound();
})->with(['missing', 'guides/missing', '..%2F..%2F.env', '../composer']);

it('hides the docs from users who are not super admins', function (): void {
    actingAs(User::factory()->create());

    $this->withExceptionHandling()->get(route('panel-admin::docs.show', ['path' => 'intro']))->assertNotFound();
});
