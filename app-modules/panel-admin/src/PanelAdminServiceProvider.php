<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin;

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use He4rt\PanelAdmin\Discord\DiscordCluster;
use He4rt\PanelAdmin\Enums\NavigationGroup as NavGroup;
use He4rt\PanelAdmin\Filament\Resources\ContentEntries\ContentEntryResource;
use He4rt\PanelAdmin\Filament\Resources\Events\EventResource;
use He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\ExternalIdentityResource;
use He4rt\PanelAdmin\Filament\Resources\Profiles\ProfileResource;
use He4rt\PanelAdmin\Filament\Resources\Skills\SkillResource;
use He4rt\PanelAdmin\Filament\Resources\Users\UserResource;
use He4rt\PanelAdmin\Github\GithubCluster;
use He4rt\PanelAdmin\Marketing\MarketingCluster;
use He4rt\PanelAdmin\Moderation\Livewire\AppealQueue;
use He4rt\PanelAdmin\Moderation\Livewire\ModerationDashboardLivewire;
use He4rt\PanelAdmin\Moderation\Livewire\ModerationQueue;
use He4rt\PanelAdmin\Moderation\ModerationCluster;
use He4rt\PanelAdmin\Pages\Dashboard;
use He4rt\PanelAdmin\Twitch\TwitchCluster;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Livewire;

class PanelAdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/panel-admin.php', 'panel-admin');

        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() !== 'admin') {
                return;
            }

            $panel
                ->pages([
                    ModerationCluster::class,
                    MarketingCluster::class,
                    TwitchCluster::class,
                    GithubCluster::class,
                    DiscordCluster::class,
                ])
                ->navigation($this->buildNavigation(...))
                ->resources([
                    ExternalIdentityResource::class,
                    EventResource::class,
                    UserResource::class,
                    ProfileResource::class,
                    SkillResource::class,
                    ContentEntryResource::class,
                ])
                ->discoverResources(
                    in: __DIR__.'/Moderation/Resources',
                    for: 'He4rt\\PanelAdmin\\Moderation\\Resources',
                )
                ->discoverPages(
                    in: __DIR__.'/Moderation/Pages',
                    for: 'He4rt\\PanelAdmin\\Moderation\\Pages',
                )
                ->discoverResources(
                    in: __DIR__.'/Marketing/Resources',
                    for: 'He4rt\\PanelAdmin\\Marketing\\Resources',
                )
                ->discoverPages(
                    in: __DIR__.'/Marketing/Pages',
                    for: 'He4rt\\PanelAdmin\\Marketing\\Pages',
                )
                ->discoverResources(
                    in: __DIR__.'/Twitch/Resources',
                    for: 'He4rt\\PanelAdmin\\Twitch\\Resources',
                )
                ->discoverPages(
                    in: __DIR__.'/Twitch/Pages',
                    for: 'He4rt\\PanelAdmin\\Twitch\\Pages',
                )
                ->discoverPages(
                    in: __DIR__.'/Discord/Pages',
                    for: 'He4rt\\PanelAdmin\\Discord\\Pages',
                )
                ->discoverResources(
                    in: __DIR__.'/Github/Resources',
                    for: 'He4rt\\PanelAdmin\\Github\\Resources',
                )
                ->discoverResources(
                    in: __DIR__.'/Discord/Resources',
                    for: 'He4rt\\PanelAdmin\\Discord\\Resources',
                );
        });
    }

    public function boot(): void
    {
        $this->configureShield();

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'panel-admin');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'panel-admin');

        Livewire::component('moderation-queue', ModerationQueue::class);
        Livewire::component('appeal-queue', AppealQueue::class);
        Livewire::component('moderation-dashboard', ModerationDashboardLivewire::class);
    }

    private function configureShield(): void
    {
        // Shield refuses the '_' separator alongside snake case, so the config carries a
        // placeholder separator and this builder joins the parts itself.
        FilamentShield::buildPermissionKeyUsing(
            fn (string $affix, string $subject): string => Str::snake($affix).'_'.Str::snake($subject),
        );

        $this->registerPanelPolicies();
    }

    /**
     * Authorising a panel screen is a presentation concern, so the policies live in this
     * module instead of beside each domain model. Neither Laravel's discovery nor
     * Shield's looks here, so teach the Gate where to look. Names that do not resolve
     * are discarded by the Gate, and the framework default stays as a fallback.
     */
    private function registerPanelPolicies(): void
    {
        Gate::guessPolicyNamesUsing(fn (string $model): array => [
            'He4rt\\PanelAdmin\\Policies\\'.class_basename($model).'Policy',
            'App\\Policies\\'.class_basename($model).'Policy',
        ]);
    }

    private function buildNavigation(NavigationBuilder $builder): NavigationBuilder
    {

        $requestPath = str(request()->path());
        // Livewire update requests go to /livewire/update, so the
        // path no longer reflects the actual page. Use the Referer
        // header to determine the real page context.
        if ($requestPath->startsWith('livewire/')) {
            $referer = request()->header('referer');
            if ($referer) {
                $requestPath = str(mb_ltrim(parse_url($referer, PHP_URL_PATH) ?: '', '/'));
            }
        }

        // Extract the segment after package-contracts/ and verify it's
        // a valid UUID pointing to an existing record. This avoids
        // false positives for non-record pages like /create.

        return match (true) {
            $requestPath->contains('mod/') => $this->moderationNavigation($builder),
            $requestPath->contains('marketing/') => $this->marketingNavigation($builder),
            $requestPath->contains('twitch/') => $this->twitchNavigation($builder),
            $requestPath->contains('discord/') => $this->discordNavigation($builder),
            default => $this->defaultNavigation($builder),
        };

    }

    private function defaultNavigation(NavigationBuilder $builder): NavigationBuilder
    {
        return $builder
            ->items([
                ...Dashboard::getNavigationItems(),
                ...ModerationCluster::getNavigationItems(),
                ...MarketingCluster::getNavigationItems(),
                ...TwitchCluster::getNavigationItems(),
                ...GithubCluster::getNavigationItems(),
                ...DiscordCluster::getNavigationItems(),
                ...EventResource::getNavigationItems(),
            ])
            ->groups([
                NavigationGroup::make(NavGroup::People->getLabel())
                    ->icon(NavGroup::People->getIcon())
                    ->items([
                        ...UserResource::getNavigationItems(),
                        ...ExternalIdentityResource::getNavigationItems(),
                        ...ProfileResource::getNavigationItems(),
                        ...SkillResource::getNavigationItems(),
                    ]),
                NavigationGroup::make(NavGroup::Content->getLabel())
                    ->icon(NavGroup::Content->getIcon())
                    ->items([
                        ...ContentEntryResource::getNavigationItems(),
                    ]),
            ]);
    }

    private function moderationNavigation(NavigationBuilder $builder): NavigationBuilder
    {
        return $builder->items([
            NavigationItem::make(__('panel-admin::moderation.navigation.back_to_admin'))
                ->sort(0)
                ->icon('heroicon-o-arrow-left')
                ->url(Dashboard::getUrl()),

        ])->groups(resolve(ModerationCluster::class)->getCachedSubNavigation());
    }

    private function discordNavigation(NavigationBuilder $builder): NavigationBuilder
    {
        return $builder->items([
            NavigationItem::make(__('panel-admin::marketing.navigation.back_to_admin'))
                ->sort(0)
                ->icon('heroicon-o-arrow-left')
                ->url(Dashboard::getUrl()),

        ])->groups(resolve(DiscordCluster::class)->getCachedSubNavigation());
    }

    private function marketingNavigation(NavigationBuilder $builder): NavigationBuilder
    {
        return $builder->items([
            NavigationItem::make(__('panel-admin::marketing.navigation.back_to_admin'))
                ->sort(0)
                ->icon('heroicon-o-arrow-left')
                ->url(Dashboard::getUrl()),

        ])->groups(resolve(MarketingCluster::class)->getCachedSubNavigation());
    }

    private function twitchNavigation(NavigationBuilder $builder): NavigationBuilder
    {
        return $builder->items([
            NavigationItem::make(__('panel-admin::twitch.navigation.back_to_admin'))
                ->sort(0)
                ->icon('heroicon-o-arrow-left')
                ->url(Dashboard::getUrl()),

        ])->groups(resolve(TwitchCluster::class)->getCachedSubNavigation());
    }
}
