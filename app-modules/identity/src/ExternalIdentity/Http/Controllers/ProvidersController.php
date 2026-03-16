<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\Http\Controllers;

use App\Http\Controllers\Controller;
use He4rt\Identity\ExternalIdentity\Actions\CreateAccountByExternalIdentity;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Http\Requests\CreateProviderRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ProvidersController extends Controller
{
    public function postProvider(
        CreateProviderRequest $request,
        string $provider,
        CreateAccountByExternalIdentity $action,
    ): JsonResponse {
        $response = $action->handle(
            $request->input('tenant_id'),
            IdentityProvider::from($provider),
            $request->input('provider_id'),
            $request->input('username')
        );

        return response()->json($response, Response::HTTP_CREATED);
    }
}
