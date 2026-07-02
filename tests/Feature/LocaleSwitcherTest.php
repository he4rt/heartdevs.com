<?php

declare(strict_types=1);

use App\Http\Middleware\SetApplicationLocale;
use App\Support\ApplicationLocale;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;

it('persists locale in session', function (): void {
    $this->from('/admin')
        ->get(route('locale.switch', ['locale' => ApplicationLocale::PT_BR]))
        ->assertRedirect('/admin');

    expect(session(ApplicationLocale::SESSION_KEY))->toBe(ApplicationLocale::PT_BR);
});

it('rejects unsupported locale', function (): void {
    $this->get('/locale/fr')->assertNotFound();
});

it('applies locale from session via middleware', function (): void {
    session([ApplicationLocale::SESSION_KEY => ApplicationLocale::PT_BR]);

    $middleware = new SetApplicationLocale;
    $request = Request::create('/');

    $middleware->handle($request, function (): ResponseFactory|Response {
        expect(app()->getLocale())->toBe(ApplicationLocale::PT_BR)
            ->and(Date::getLocale())->toBe('pt_BR');

        return response('ok');
    });
});

it('falls back to english for invalid session locale', function (): void {
    session([ApplicationLocale::SESSION_KEY => 'invalid']);

    expect(ApplicationLocale::resolve())->toBe(ApplicationLocale::EN);
});

it('renders locale switcher with supported locales', function (): void {
    $html = Blade::render('<x-filament.locale-switcher />');

    expect($html)
        ->toContain('EN')
        ->toContain('PT-BR')
        ->toContain(route('locale.switch', ['locale' => ApplicationLocale::EN]))
        ->toContain(route('locale.switch', ['locale' => ApplicationLocale::PT_BR]));
});
