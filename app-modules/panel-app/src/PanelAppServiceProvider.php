<?php

declare(strict_types=1);

namespace He4rt\PanelApp;

use He4rt\PanelApp\Livewire\Events\EventDetail;
use He4rt\PanelApp\Livewire\Events\EventsList;
use He4rt\PanelApp\Livewire\Events\MyEventsList;
use He4rt\PanelApp\Livewire\Events\NumericCodeCheckIn;
use He4rt\PanelApp\Livewire\Timeline\Composer;
use He4rt\PanelApp\Livewire\Timeline\Feed;
use He4rt\PanelApp\Livewire\Timeline\PostShow;
use He4rt\PanelApp\Livewire\Timeline\ReplyComposer;
use He4rt\PanelApp\Livewire\Timeline\ThreadReplies;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class PanelAppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'panel-app');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'panel-app');

        Livewire::component('events-list', EventsList::class);
        Livewire::component('my-events-list', MyEventsList::class);
        Livewire::component('event-detail', EventDetail::class);
        Livewire::component('numeric-code-check-in', NumericCodeCheckIn::class);

        Livewire::component('timeline-composer', Composer::class);
        Livewire::component('timeline-feed', Feed::class);
        Livewire::component('timeline-post-show', PostShow::class);
        Livewire::component('timeline-reply-composer', ReplyComposer::class);
        Livewire::component('timeline-thread-replies', ThreadReplies::class);
    }
}
