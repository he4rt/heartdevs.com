<?php

declare(strict_types=1);

namespace App\Mcp;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Str;
use InterNACHI\Modular\Support\ModuleRegistry;
use Laravel\Mcp\Server\Tool;

/**
 * A tool of the development server. The name starts with the module that owns the tool,
 * and every tool accepts `acting_as`, which {@see RecordedCallTool} reads before the tool runs.
 */
abstract class DevelopmentTool extends Tool
{
    public const string ACTING_AS = 'acting_as';

    /**
     * @return array<string, Type>
     */
    abstract protected function arguments(JsonSchema $schema): array;

    /**
     * The values that the action decided or derived. Never repeat the arguments.
     *
     * @return array<string, Type>
     */
    abstract protected function output(JsonSchema $schema): array;

    public function name(): string
    {
        $module = resolve(ModuleRegistry::class)->moduleForClass(static::class);
        $name = Str::of(class_basename(static::class))->beforeLast('Tool')->kebab();

        return $module === null ? $name->toString() : $name->prepend("{$module->name}-")->toString();
    }

    public function title(): string
    {
        return Str::of(class_basename(static::class))->beforeLast('Tool')->headline()->toString();
    }

    /**
     * @return array<string, Type>
     */
    final public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->arguments($schema),
            self::ACTING_AS => $schema->string()
                ->format('uuid')
                ->description('The id of the user that runs the tool. Leave it out to run as a guest.'),
        ];
    }

    /**
     * {@see RecordedToolInvoker} adds `request_id` and `activity` to every result.
     *
     * @return array<string, Type>
     */
    final public function outputSchema(JsonSchema $schema): array
    {
        return [
            ...$this->output($schema),
            'request_id' => $schema->string()
                ->description('Finds the wide event of the call: read development://events/{request_id}.')
                ->required(),
            'activity' => $schema->array()
                ->items($schema->object([
                    'event' => $schema->string()->nullable(),
                    'subject_type' => $schema->string()->nullable(),
                    'subject_id' => $schema->string()->nullable(),
                ]))
                ->description('The activity log entries that the call wrote, oldest first.')
                ->required(),
        ];
    }
}
