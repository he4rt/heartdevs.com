<?php

declare(strict_types=1);

namespace He4rt\Gamification\Character\Http\Controllers;

use App\Http\Controllers\Controller;
use He4rt\Gamification\Character\Actions\ClaimCharacterBadge;
use He4rt\Gamification\Character\Actions\ClaimDailyBonus;
use He4rt\Gamification\Character\Exceptions\CharacterException;
use He4rt\Gamification\Character\Http\Requests\ClaimBadgeRequest;
use He4rt\Gamification\Character\Models\Character;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class CharactersController extends Controller
{
    public function getCharacters(): JsonResponse
    {
        return response()->json(Character::query()->paginate());
    }

    public function getCharacter(string $providerId): JsonResponse
    {
        $character = Character::query()->findOrFail($providerId);

        return response()->json($character);
    }

    public function postDailyBonus(
        string $provider,
        string $providerId,
        ClaimDailyBonus $action
    ): Response|JsonResponse {
        try {
            $action->handle($provider, $providerId);

            return response()->noContent();
        } catch (CharacterException $characterException) {
            return response()->json($characterException->getMessage(), $characterException->getCode());
        }
    }

    public function postClaimBadge(
        ClaimBadgeRequest $request,
        string $provider,
        string $providerId,
        ClaimCharacterBadge $claimBadge
    ): Response {
        $claimBadge->handle($provider, $providerId, $request->input('redeem_code'));

        return response()->noContent();
    }
}
