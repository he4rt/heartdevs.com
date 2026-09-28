<?php

declare(strict_types=1);

namespace He4rt\Profile\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use He4rt\Profile\Http\Resources\ProfileResource;
use He4rt\Profile\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class MobileProfileController extends Controller
{
    /**
     * Perfil do usuário autenticado
     *
     * Retorna o perfil (dados profissionais, skills e experiências) do dono do token JWT atual.
     */
    public function __invoke(Request $request): JsonResponse
    {
        /** @var string $userId */
        $userId = $request->user()->id;

        $profile = Profile::ensureExists($userId)
            ->load(['profileSkills.skill', 'workExperiences']);

        // ensureExists() pode ter acabado de criar o profile (firstOrCreate);
        // ResourceResponse herdaria o 201 do model pra uma rota GET.
        return new ProfileResource($profile)
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }
}
