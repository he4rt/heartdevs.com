<?php

declare(strict_types=1);

namespace He4rt\Identity\Permissions\Commands\SyncPermissions;

use He4rt\Identity\Permissions\PermissionsEnum;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelSettings\Settings;

final readonly class ModelPayload
{
    /**
     * @param  class-string<Model>|class-string<Settings>  $resource
     * @param  list<PermissionsEnum>  $actions
     */
    public function __construct(
        public string $name,
        public string $resource,
        public string $resourceGroup,
        public array $actions,
    ) {}

    /**
     * @return array{name: string, resource: string, resource_group: string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'resource' => $this->resource,
            'resource_group' => $this->resourceGroup,
        ];
    }
}
