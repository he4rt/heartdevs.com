<?php

declare(strict_types=1);

namespace He4rt\Provider\Http\Controller;

use App\Http\Controllers\Controller;
use He4rt\Provider\Actions\NewAccountByProvider;
use He4rt\Provider\Enums\ProviderEnum;
use He4rt\Provider\Http\Requests\CreateProviderRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ProvidersController extends Controller
{
    public function postProvider(
        CreateProviderRequest $request,
        string $provider,
        NewAccountByProvider $action,
    ): JsonResponse {
        $response = $action->handle(
            $request->input('tenant_id'),
            ProviderEnum::from($provider),
            $request->input('provider_id'),
            $request->input('username')
        );

        return response()->json($response, Response::HTTP_CREATED);
    }
}
