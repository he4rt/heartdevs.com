<?php

declare(strict_types=1);

namespace He4rt\Profile\Http\Controllers;

use App\Http\Controllers\Controller;
use He4rt\Identity\User\Models\User;
use He4rt\Profile\Actions\BuildProfileCard;
use He4rt\Profile\Queries\FindPublicProfileUser;
use He4rt\Profile\Support\PublicProfileCache;
use Illuminate\Http\Response;

final class ProfileCardController extends Controller
{
    public function __construct(
        private readonly BuildProfileCard $buildProfileCard,
        private readonly FindPublicProfileUser $findPublicProfileUser,
    ) {}

    public function __invoke(string $username): Response
    {
        abort_unless(auth()->check(), 401);

        $user = $this->findPublicProfileUser->handle($username);

        abort_unless($user instanceof User, 404);

        return response()
            ->view('profile::card', [
                'card' => $this->buildProfileCard->handle($user),
            ])
            ->header('Cache-Control', 'private, max-age='.PublicProfileCache::TTL_SECONDS);
    }
}
