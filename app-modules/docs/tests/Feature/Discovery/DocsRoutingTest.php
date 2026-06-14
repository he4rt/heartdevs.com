<?php

declare(strict_types=1);

use He4rt\Docs\DocsController;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;

beforeEach(function (): void {
    $this->withoutVite();
    config()->set('docs.cache.enabled', false);
});

it('serves an ADR page publicly with status badge and stripped title', function (): void {
    $this->get('/docs/decisions/moderation/0001-hybrid-pipeline-with-event-driven-enforcement')
        ->assertOk()
        ->assertSee('Hybrid Pipeline with Event-Driven Enforcement')
        ->assertSee('Aceito')
        ->assertSee('danielhe4rt')
        ->assertSee('Decisões');
});

it('renders a module glossary page', function (): void {
    $this->get('/docs/glossary/moderation')
        ->assertOk()
        ->assertSee('Moderation Context');
});

it('redirects the docs index to the first document', function (): void {
    $this->get('/docs')->assertRedirect();
});

it('does not let the catch-all hijack the Scramble api route', function (): void {
    $route = resolve(Router::class)->getRoutes()->match(
        Request::create('/docs/3.x/api', 'GET'),
    );

    expect($route->getActionName())->not->toContain(DocsController::class);
});

it('returns 404 for an unknown section', function (): void {
    $this->get('/docs/foo')->assertNotFound();
});

it('returns 404 for a known section with a missing document', function (): void {
    $this->get('/docs/decisions/moderation/does-not-exist')->assertNotFound();
});
