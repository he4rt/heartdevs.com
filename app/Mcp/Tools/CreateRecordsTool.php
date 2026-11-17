<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Mcp\DevelopmentTool;
use App\Mcp\ModuleClasses;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use ReflectionClass;
use ReflectionMethod;

#[Description(value: 'Creates records with the model factory of a module and returns their keys. Use it for supporting data only: when a module has a tool for the action (such as identity-create-team), use that tool, because it runs the business rules.')]
#[IsReadOnly(value: false)]
#[IsDestructive(value: false)]
#[IsIdempotent(value: false)]
#[IsOpenWorld(value: false)]
final class CreateRecordsTool extends DevelopmentTool
{
    private const int MAX_COUNT = 50;

    public function handle(Request $request): ResponseFactory
    {
        $factories = $this->factories();

        $validated = $request->validate([
            'model' => ['required', 'string', Rule::in($factories->keys()->all())],
            'states' => ['array'],
            'states.*' => ['string'],
            'count' => ['integer', 'between:1,'.self::MAX_COUNT],
            'attributes' => ['array'],
        ], [
            'model.in' => sprintf('Unknown model. Use one of: %s.', $factories->keys()->implode(', ')),
        ]);

        $model = $validated['model'];
        $factory = $factories[$model];
        $states = $request->collect('states')->map(fn (mixed $state): string => is_string($state) ? $state : '');
        $knownStates = $this->statesOf($factory);
        $unknownStates = $states->reject(fn (string $state): bool => in_array($state, $knownStates, strict: true));

        throw_if($unknownStates->isNotEmpty(), ValidationException::withMessages([
            'states' => sprintf('Unknown state [%s]. %s has: %s.', $unknownStates->implode(', '), $model, implode(', ', $knownStates)),
        ]));

        foreach ($states as $state) {
            $factory = $this->applyState($factory, $state);
        }

        $attributes = $request->collect('attributes')->mapWithKeys(fn (mixed $value, int|string $key): array => [(string) $key => $value])->all();
        $records = $factory->count($request->integer('count', 1))->create($attributes);

        return Response::structured(['model' => $model, 'keys' => $records->modelKeys()]);
    }

    /**
     * @return array<string, Type>
     */
    protected function output(JsonSchema $schema): array
    {
        return [
            'model' => $schema->string()->required(),
            'keys' => $schema->array()->items($schema->string())->description('The keys of the new records.')->required(),
        ];
    }

    /**
     * @return array<string, Type>
     */
    protected function arguments(JsonSchema $schema): array
    {
        $factories = $this->factories();

        return [
            'model' => $schema->string()
                ->enum($factories->keys()->all())
                ->description('The model to create.')
                ->required(),
            'states' => $schema->array()
                ->items($schema->string())
                ->description(sprintf(
                    'Factory states to apply, in order. %s.',
                    $factories->map(fn (Factory $factory, string $model): string => sprintf('%s: %s', $model, implode(', ', $this->statesOf($factory)) ?: 'none'))->implode('; '),
                )),
            'count' => $schema->integer()
                ->min(1)
                ->max(self::MAX_COUNT)
                ->default(1)
                ->description('How many records to create.'),
            'attributes' => $schema->object()
                ->description('Attributes that replace the factory values, such as {"name": "Ana"}.'),
        ];
    }

    /**
     * @return Collection<string, Factory<*>> the factory of each model, keyed by the model class basename
     */
    private function factories(): Collection
    {
        return once(fn (): Collection => resolve(ModuleClasses::class)->in('database/factories', '*Factory.php')
            ->map(fn (string $class): ?Factory => $this->factoryOf($class))
            ->filter()
            ->mapWithKeys(fn (Factory $factory): array => [class_basename($factory->modelName()) => $factory])
            ->sortKeys());
    }

    /**
     * @return Factory<*>|null
     */
    private function factoryOf(string $class): ?Factory
    {
        return is_subclass_of($class, Factory::class) && !new ReflectionClass($class)->isAbstract() ? $class::new() : null;
    }

    /**
     * @param  Factory<*>  $factory
     * @return Factory<*>
     */
    private function applyState(Factory $factory, string $state): Factory
    {
        $next = $factory->{$state}();

        throw_unless($next instanceof Factory, InvalidArgumentException::class, sprintf('The state [%s] does not return a factory.', $state));

        return $next;
    }

    /**
     * A state is a public method of the factory itself that needs no argument.
     *
     * @param  Factory<*>  $factory
     * @return array<int, string>
     */
    private function statesOf(Factory $factory): array
    {
        return collect(new ReflectionClass($factory)->getMethods(ReflectionMethod::IS_PUBLIC))
            ->filter(fn (ReflectionMethod $method): bool => $method->class === $factory::class
                && !$method->isStatic()
                && $method->getNumberOfRequiredParameters() === 0
                && !in_array($method->name, ['definition', 'configure'], strict: true))
            ->map(fn (ReflectionMethod $method): string => $method->name)
            ->sort()
            ->values()
            ->all();
    }
}
