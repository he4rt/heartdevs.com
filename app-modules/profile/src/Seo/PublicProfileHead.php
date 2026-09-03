<?php

declare(strict_types=1);

namespace He4rt\Profile\Seo;

use He4rt\Profile\DTOs\ProfileSkillData;
use He4rt\Profile\DTOs\PublicProfileData;
use He4rt\Profile\Enums\SocialPlatform;
use Illuminate\Support\Str;
use Laravel\Head\Enums\OgType;
use Laravel\Head\Facades\Head;
use Laravel\Head\Facades\Schema;
use Laravel\Head\Schema\Person;

final class PublicProfileHead
{
    private const int DESCRIPTION_LIMIT = 157;

    public static function apply(PublicProfileData $profile): void
    {
        $description = self::description($profile);
        $url = self::url($profile);
        $image = self::absolute($profile->coverUrl ?? $profile->avatarUrl);

        Head::title($profile->name)
            ->description($description)
            ->og(type: OgType::Profile, url: $url)
            ->schema(self::person($profile, $description, $url, $image));

        if ($image !== null) {
            Head::ogImage($image, alt: $profile->name);
        }

        $creator = self::twitterHandle($profile);

        if ($creator !== null) {
            Head::twitter(creator: $creator);
        }
    }

    private static function description(PublicProfileData $profile): string
    {
        $role = $profile->currentPosition !== null && $profile->currentCompany !== null
            ? $profile->currentPosition.' · '.$profile->currentCompany
            : $profile->currentPosition;

        $description = collect([$profile->headline, $role, $profile->about])
            ->filter()
            ->first() ?? $profile->name.' na comunidade He4rt Developers.';

        return Str::limit($description, self::DESCRIPTION_LIMIT);
    }

    private static function url(PublicProfileData $profile): string
    {
        return secure_url(route('profile.public', ['username' => $profile->username], absolute: false));
    }

    private static function absolute(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        return str_starts_with($url, '/') ? url($url) : $url;
    }

    private static function person(PublicProfileData $profile, string $description, string $url, ?string $image): Person
    {
        $person = Schema::person()
            ->name($profile->name)
            ->url($url)
            ->set('description', $description)
            ->set('alternateName', '@'.$profile->username);

        if ($image !== null) {
            $person->set('image', $image);
        }

        if ($profile->currentPosition !== null) {
            $person->set('jobTitle', $profile->currentPosition);
        }

        if ($profile->currentCompany !== null) {
            $person->set('worksFor', ['@type' => 'Organization', 'name' => $profile->currentCompany]);
        }

        $sameAs = self::sameAs($profile);

        if ($sameAs !== []) {
            $person->set('sameAs', $sameAs);
        }

        $knowsAbout = array_map(
            static fn (ProfileSkillData $skill): string => $skill->name,
            $profile->skills,
        );

        if ($knowsAbout !== []) {
            $person->set('knowsAbout', $knowsAbout);
        }

        return $person;
    }

    /**
     * @return list<string>
     */
    private static function sameAs(PublicProfileData $profile): array
    {
        $urls = [];

        foreach ([...$profile->socialLinks, ...$profile->connectedAccounts] as $link) {
            if ($link->url !== null) {
                $urls[] = $link->url;
            }
        }

        return array_values(array_unique($urls));
    }

    private static function twitterHandle(PublicProfileData $profile): ?string
    {
        foreach ($profile->socialLinks as $link) {
            if ($link->icon !== SocialPlatform::Twitter->getBrandIcon()) {
                continue;
            }

            $handle = mb_ltrim(mb_trim($link->handle), '@');

            return $handle === '' || str_contains($handle, '/') ? null : '@'.$handle;
        }

        return null;
    }
}
