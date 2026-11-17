<?php

declare(strict_types=1);

namespace He4rt\Identity\Permissions;

use App\Contracts\Enums\HasLabel;
use App\Contracts\Enums\HasTone;
use App\Enums\Tone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Spatie\LaravelSettings\Settings;

enum PermissionsEnum: string implements HasLabel, HasTone
{
    case View = 'view';
    case ViewAny = 'view_any';
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
    case Restore = 'restore';
    case ForceDelete = 'force_delete';

    public function getLabel(): string
    {
        return match ($this) {
            self::View => 'View',
            self::ViewAny => 'View Any',
            self::Create => 'Create',
            self::Update => 'Update',
            self::Delete => 'Delete',
            self::Restore => 'Restore',
            self::ForceDelete => 'Force Delete',
        };
    }

    public function getTone(): Tone
    {
        return match ($this) {
            self::View, self::ViewAny => Tone::Neutral,
            self::Create => Tone::Success,
            self::Update => Tone::Info,
            self::Restore => Tone::Warning,
            self::Delete, self::ForceDelete => Tone::Danger,
        };
    }

    /**
     * A settings group has no morph alias, so its permission names the group: `update_authentication_settings`.
     *
     * @param  class-string<Model>|class-string<Settings>  $resource
     */
    public function buildPermissionFor(string $resource): string
    {
        if (is_subclass_of($resource, Settings::class)) {
            return sprintf('%s_%s_settings', $this->value, $resource::group());
        }

        /** @var class-string<Model> $resource */
        return sprintf('%s_%s', $this->value, $this->snakeMorphAlias($resource));
    }

    /**
     * @param  class-string<Model>  $classPath
     */
    private function snakeMorphAlias(string $classPath): string
    {
        return Str::snake((string) Relation::getMorphAlias($classPath));
    }
}
