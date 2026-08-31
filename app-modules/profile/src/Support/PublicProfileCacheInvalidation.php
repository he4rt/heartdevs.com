<?php

declare(strict_types=1);

namespace He4rt\Profile\Support;

use App\Models\Address;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\Profile\Models\Profile;
use He4rt\Profile\Models\ProfileProject;
use He4rt\Profile\Models\ProfileSkill;
use He4rt\Profile\Models\WorkExperience;
use Illuminate\Database\Eloquent\Relations\Relation;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Stringable;

final class PublicProfileCacheInvalidation
{
    private const array USER_MEDIA_COLLECTIONS = ['avatar', 'cover'];

    public static function register(): void
    {
        self::profileOwned();
        self::userOwned();
    }

    private static function profileOwned(): void
    {
        Profile::saved(static fn (Profile $row): null => self::forgetProfile($row));
        Profile::deleted(static fn (Profile $row): null => self::forgetProfile($row));

        WorkExperience::saved(static fn (WorkExperience $row): null => self::forgetProfile($row->profile));
        WorkExperience::deleted(static fn (WorkExperience $row): null => self::forgetProfile($row->profile));

        ProfileSkill::saved(static fn (ProfileSkill $row): null => self::forgetProfile($row->profile));
        ProfileSkill::deleted(static fn (ProfileSkill $row): null => self::forgetProfile($row->profile));

        ProfileProject::saved(static fn (ProfileProject $row): null => self::forgetProfile($row->profile));
        ProfileProject::deleted(static fn (ProfileProject $row): null => self::forgetProfile($row->profile));
    }

    private static function userOwned(): void
    {
        User::saved(static fn (User $row): null => self::forget((string) $row->getKey()));
        User::deleted(static fn (User $row): null => self::forget((string) $row->getKey()));

        Address::saved(static fn (Address $row): null => self::forgetMorph($row->addressable_type, $row->addressable_id));
        Address::deleted(static fn (Address $row): null => self::forgetMorph($row->addressable_type, $row->addressable_id));

        ExternalIdentity::saved(static fn (ExternalIdentity $row): null => self::forgetMorph($row->model_type, $row->model_id));
        ExternalIdentity::deleted(static fn (ExternalIdentity $row): null => self::forgetMorph($row->model_type, $row->model_id));

        Media::saved(static fn (Media $row): null => self::forgetUserMedia($row));
        Media::deleted(static fn (Media $row): null => self::forgetUserMedia($row));
    }

    private static function forgetProfile(?Profile $profile): null
    {
        return $profile instanceof Profile
            ? self::forget((string) $profile->user_id)
            : null;
    }

    private static function forgetUserMedia(Media $media): null
    {
        return in_array($media->collection_name, self::USER_MEDIA_COLLECTIONS, strict: true)
            ? self::forgetMorph($media->model_type, $media->model_id)
            : null;
    }

    private static function forgetMorph(?string $type, int|string|Stringable|null $id): null
    {
        return self::isUser($type) ? self::forget($id) : null;
    }

    private static function isUser(?string $type): bool
    {
        if ($type === null || $type === '') {
            return false;
        }

        return is_a(Relation::getMorphedModel($type) ?? $type, User::class, allow_string: true);
    }

    private static function forget(int|string|Stringable|null $userId): null
    {
        $key = (string) $userId;

        if ($key !== '') {
            PublicProfileCache::forget($key);
        }

        return null;
    }
}
