<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Users\Http\Controllers;

use He4rt\PanelAdmin\Features\Setup\Users\Data\UserOptionData;
use He4rt\PanelAdmin\Features\Setup\Users\Http\Requests\ListUserOptionsRequest;
use He4rt\PanelAdmin\Features\Setup\Users\Queries\ListUserOptions;
use Illuminate\Http\JsonResponse;

/**
 * JSON for the async user pickers.
 */
final readonly class UserOptionController
{
    public function index(ListUserOptionsRequest $request, ListUserOptions $options): JsonResponse
    {
        $users = $options->handle($request->search(), $request->excludedTeamId());

        return response()->json(['data' => $users->map(UserOptionData::fromModel(...))->values()]);
    }
}
