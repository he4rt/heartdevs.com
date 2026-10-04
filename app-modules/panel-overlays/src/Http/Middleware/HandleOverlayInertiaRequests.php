<?php

declare(strict_types=1);

namespace He4rt\PanelOverlays\Http\Middleware;

use Inertia\Middleware;

class HandleOverlayInertiaRequests extends Middleware
{
    protected $rootView = 'panel-overlays::app';

    protected $withoutSsr = ['overlay/*'];
}
