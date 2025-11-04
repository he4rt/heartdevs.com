<?php

declare(strict_types=1);

namespace He4rt\Portal\Http\Controllers;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

class PortalController
{
    public function __invoke(): Factory|View
    {
        return view('portal::homepage');
    }
}
