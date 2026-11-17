<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Mcp\Resources\EventsResource;
use App\Mcp\Tools\CreateRecordsTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Resource;
use ReflectionClass;

#[Name(value: 'Development')]
#[Version(value: '1.0.0')]
#[Instructions(value: <<<'MARKDOWN'
    Tools for local development. They run the domain actions and the model factories against the local database and return the keys of the records.
    - Prefer the tool of a domain action to create-records: the action runs the business rules (activity log, membership, events).
    - Pass `acting_as` with a user id to run a tool as that user.
    - A result holds what the action decided, the `request_id` of the call and the `activity` it wrote. Its resource links (development://...) give the current state of a record: read them only when you need more.
    - Read development://events/{request_id} to see the queries, the duration and the exception of a call.
    MARKDOWN)]
final class DevelopmentServer extends Server
{
    /**
     * @var array<int, class-string<DevelopmentTool>>
     */
    protected array $tools = [
        CreateRecordsTool::class,
    ];

    /**
     * @var array<int, class-string<resource>>
     */
    protected array $resources = [
        EventsResource::class,
    ];

    protected function boot(): void
    {
        $this->addMethod('tools/call', RecordedCallTool::class);
        $this->tools = [...$this->tools, ...$this->discover('src/Mcp/Tools', '*Tool.php', DevelopmentTool::class)];
        $this->resources = [...$this->resources, ...$this->discover('src/Mcp/Resources', '*Resource.php', Resource::class)];
    }

    /**
     * Every concrete class of the folder of a module that extends the parent.
     *
     * @template TParent of object
     *
     * @param  class-string<TParent>  $parent
     * @return array<int, class-string<TParent>>
     */
    private function discover(string $folder, string $fileName, string $parent): array
    {
        return resolve(ModuleClasses::class)->in($folder, $fileName)
            ->map(fn (string $class): ?string => $this->concrete($class, $parent))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @template TParent of object
     *
     * @param  class-string<TParent>  $parent
     * @return class-string<TParent>|null
     */
    private function concrete(string $class, string $parent): ?string
    {
        return is_subclass_of($class, $parent) && !new ReflectionClass($class)->isAbstract() ? $class : null;
    }
}
