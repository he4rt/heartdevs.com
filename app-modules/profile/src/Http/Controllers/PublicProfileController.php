<?php

declare(strict_types=1);

namespace He4rt\Profile\Http\Controllers;

use App\Http\Controllers\Controller;
use He4rt\Identity\User\Models\User;
use He4rt\Profile\Actions\BuildPublicProfile;
use He4rt\Profile\Queries\FindPublicProfileUser;
use He4rt\Profile\Seo\PublicProfileHead;
use He4rt\Profile\Support\PublicProfileCache;
use Illuminate\Http\Response;

final class PublicProfileController extends Controller
{
    public function __construct(
        private readonly BuildPublicProfile $buildPublicProfile,
        private readonly FindPublicProfileUser $findPublicProfileUser,
    ) {}

    public function __invoke(string $username): Response
    {
        $user = $this->findPublicProfileUser->handle($username);

        abort_unless($user instanceof User, 404);

        $profile = $this->buildPublicProfile->handle($user);

        PublicProfileHead::apply($profile);

        return response()
            ->view('profile::public', ['profile' => $profile])
            ->header('Cache-Control', 'private, max-age='.PublicProfileCache::TTL_SECONDS);
    }
}
