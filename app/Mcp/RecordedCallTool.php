<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Support\Logging\RequestId;
use App\Support\Logging\WideEvent;
use App\Support\Logging\WideEventKind;
use Generator;
use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Mcp\Enums\ErrorCode;
use Laravel\Mcp\Exceptions\JsonRpcException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Contracts\Errable;
use Laravel\Mcp\Server\Contracts\Method;
use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Transport\JsonRpcRequest;
use Laravel\Mcp\Transport\JsonRpcResponse;
use Throwable;

/**
 * Replaces the `tools/call` method of the package, which has no hook around a call.
 * Each call is one unit of work: a new request id, one `mcp` wide event, and the `acting_as` user signed in only while the tool runs.
 * The stdio process serves the whole session, so the scoped instances are forgotten before each call, as the queue worker does.
 * A call that ends in an error result changes nothing: it runs in a transaction that rolls back.
 */
final readonly class RecordedCallTool implements Errable, Method
{
    public function handle(JsonRpcRequest $request, ServerContext $context): Generator|JsonRpcResponse
    {
        throw_unless(app()->environment('local', 'testing'),
            JsonRpcException::class,
            'The development server only runs in the local and testing environments.',
            ErrorCode::INVALID_REQUEST->value,
            $request->id);

        $tool = $this->tool($request, $context);
        $actingAs = $this->actingAs($request);

        app()->forgetScopedInstances();
        RequestId::begin(request());

        $event = resolve(WideEvent::class);
        $event->start(WideEventKind::Mcp);
        $event->set('mcp.tool', $tool->name());

        if ($actingAs instanceof User) {
            Auth::setUser($actingAs);
        }

        $signedIn = Auth::hasUser() ? Auth::user() : null;

        if ($signedIn instanceof User) {
            $event->identify($signedIn);
        }

        $arguments = collect($request->toRequest()->all())->except(DevelopmentTool::ACTING_AS)->all();
        app()->instance('mcp.request', new Request($arguments, $request->meta()));

        DB::beginTransaction();

        try {
            $response = (new RecordedToolInvoker)->invoke($tool, $request);
        } catch (Throwable $throwable) {
            DB::rollBack();

            throw $throwable;
        }

        if ($response instanceof Generator) {
            return $this->streamed($response, $actingAs instanceof User);
        }

        return $this->finish($response, $actingAs instanceof User);
    }

    /**
     * @param  Generator<int, JsonRpcResponse>  $responses
     * @return Generator<int, JsonRpcResponse>
     */
    private function streamed(Generator $responses, bool $signsOut): Generator
    {
        try {
            foreach ($responses as $response) {
                $isTheResult = array_key_exists('id', $response->content);

                yield $isTheResult ? $this->finish($response, $signsOut) : $response;
            }
        } catch (Throwable $throwable) {
            DB::rollBack();

            throw $throwable;
        }
    }

    private function finish(JsonRpcResponse $response, bool $signsOut): JsonRpcResponse
    {
        $isError = data_get($response->content, 'result.isError') === true;
        $isError ? DB::rollBack() : DB::commit();

        $event = resolve(WideEvent::class);
        $event->set('mcp.is_error', $isError);
        $event->finish();

        if ($signsOut) {
            Auth::forgetUser();
        }

        return $response;
    }

    private function tool(JsonRpcRequest $request, ServerContext $context): Tool
    {
        $name = $request->get('name');

        return $context->tools()->first(
            fn (Tool $tool): bool => $tool->name() === $name,
            fn (): never => throw new JsonRpcException(
                sprintf('Tool [%s] not found.', is_string($name) ? $name : ''),
                ErrorCode::INVALID_PARAMS->value,
                $request->id,
            ),
        );
    }

    private function actingAs(JsonRpcRequest $request): ?User
    {
        $id = $request->toRequest()->get(DevelopmentTool::ACTING_AS);

        if ($id === null) {
            return null;
        }

        $user = is_string($id) && Str::isUuid($id) ? User::query()->find($id) : null;

        return $user ?? throw new JsonRpcException(
            sprintf('No user has the id [%s] given in [acting_as].', is_scalar($id) ? $id : ''),
            ErrorCode::INVALID_PARAMS->value,
            $request->id,
        );
    }
}
