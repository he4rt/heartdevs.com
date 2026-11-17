---
paths:
  - 'app/Mcp/**'
  - 'app-modules/*/src/Mcp/**'
---

# Development MCP server

`App\Mcp\DevelopmentServer` is an MCP server for local development. An agent in the harness calls its tools to create the state a test needs, through the domain actions and the model factories, instead of PHP in tinker. It runs over stdio (`php artisan mcp:start development`) and exists only in the `local` and `testing` environments. The plan is `docs/plans/2026-11-13-dev-mcp.md`.

## Turn it on

`.mcp.json` is ignored by git, so every developer adds the server once. Start PHP where it reaches the database. On lerd, the database host (`lerd-postgres`) resolves only inside the PHP container, so the server starts through `lerd php`:

```bash
claude mcp add -s local development -- lerd php artisan mcp:start development
```

With PHP and the database on the host, use `php artisan mcp:start development`. A tool that fails with `could not translate host name` runs on the wrong side.

`lerd php artisan mcp:inspector development` opens the MCP Inspector to try a tool by hand.

## One tool per action

- A tool of a module lives in `app-modules/<module>/src/Mcp/Tools/<SubDomain>/<Action>Tool.php` and extends `App\Mcp\DevelopmentTool`. The server finds it: never register it by hand.
- The name and the title come from the class without `Tool`, with the module first: `CreateTeamTool` in `identity` is `identity-create-team`.
- The tool is a thin adapter, like a controller. `handle()` validates, finds the models by their keys, calls one action and returns. Business rules stay in the action.
- Inject the action into `handle()`. Never write to a model directly when an action does it: the action writes the activity log, the membership and the events.
- `create-records` (the model factories) is for supporting data only. When an action exists, the tool of the action is the right one.

```php
#[Description(value: 'Adds a user to a team and writes the member_added entry to the activity log. Adding a member twice changes nothing.')]
#[IsReadOnly(value: false)]
#[IsDestructive(value: false)]
#[IsIdempotent(value: true)]
#[IsOpenWorld(value: false)]
final class AddTeamMemberTool extends DevelopmentTool
{
    public function handle(Request $request, AddTeamMember $addTeamMember): ResponseFactory
    {
        $request->validate([
            'team_id' => ['required', 'uuid', Rule::exists(Team::class, 'id')],
            'user_id' => ['required', 'uuid', Rule::exists(User::class, 'id')],
        ]);

        $team = Team::query()->findOrFail($request->string('team_id')->toString());
        $user = User::query()->findOrFail($request->string('user_id')->toString());
        $added = $addTeamMember->handle($team, $user);

        return Response::make([TeamResource::link($team), UserResource::link($user)])->withStructuredContent([
            'added' => $added,
            'members' => $team->members()->count(),
        ]);
    }
}
```

## Arguments

- Declare the input in `arguments()`, not `schema()`. The base adds `acting_as` (the uuid of a user) to every tool, and `RecordedCallTool` signs that user in only while the tool runs.
- Validate with `$request->validate()`. The message goes to the model, so a custom message says what to send instead.

## The result is a receipt

An agent chains calls, and every result stays in its context. The result says what the model cannot know from its own arguments, and links to the rest.

- Return the keys and the values that the action decided or derived: a generated slug, a default status, a count after the change, `added: false` on a repeat. Never repeat an argument.
- Declare them in `output()`, not `outputSchema()`. `RecordedToolInvoker` adds `request_id` and `activity` (the activity log entries of the call) to every result, and the base declares both.
- The model reads the content of a result, not its `_meta`. Never put in `_meta` something the model needs.
- Link each record that the call touched with the `link()` of its resource: `Response::make([TeamResource::link($team)])->withStructuredContent([...])`. The agent reads the link only when it needs more.
- Never return an email, a name, a token or a whole model in the receipt (`.ai/domain/04-data`).
- An exception becomes a short error result: its class, the message (for a query error, the message of the database without the SQL and its bindings) and a pointer to `development://events/{request_id}`, where the wide event has the frames. Throw it, do not catch it in the tool.
- Each call runs in a transaction. A call that ends in an error result rolls back, so it never leaves half the records behind. A tool never commits or opens a transaction to escape that.

## Resources

- A resource of a module lives in `app-modules/<module>/src/Mcp/Resources/<SubDomain>/<Model>Resource.php`, extends `Laravel\Mcp\Server\Resource` and implements `HasUriTemplate`. The server finds it.
- The URI is `development://<module>/<plural model>/{<model>}`, and the name is `<module>-<model>`. A static `uriFor()` builds the URI, and a static `link()` builds the resource link for a tool.
- It shows the current state of one record, as JSON: what the record is, its relations as keys and names, and its last activity log entries. A trashed record still shows, with `deleted_at`.
- It shows no more than the admin panel shows a super admin. The email of a user is there, so an agent can sign in as that user. A secret never is: no password hash, no two factor secret or recovery codes, no token.
- A missing record returns `Response::error()` with the id.
- `App\Mcp\Resources\EventsResource` (`development://events/{requestId}`) reads the wide events of a call: the queries, the duration and the exception.

## Annotations

Declare the four annotations on every tool. When one is missing, the protocol reads `destructiveHint: true` and `openWorldHint: true`.

| Tool                                   | `IsReadOnly` | `IsDestructive` | `IsIdempotent`         | `IsOpenWorld` |
| -------------------------------------- | ------------ | --------------- | ---------------------- | ------------- |
| Reads only                             | `true`       | `false`         | `true`                 | `false`       |
| Creates or changes                     | `false`      | `false`         | `true` when a repeat changes nothing | `false` |
| Deletes, removes, trashes              | `false`      | `true`          | `true` when a repeat changes nothing | `false` |

## Wide events

- Every call is one unit of work: a new request id and one wide event of kind `mcp`, always kept. The core sets `mcp.tool` and `mcp.is_error`. The receipt has the request id in `request_id`.
- Add context to the event with `WideEvent::add()`, as in any other code (`.ai/rules/logging.md`).

## Boundaries

- Only `App\Mcp` and the `Mcp` folder of a domain module use `Laravel\Mcp`. A panel never does. `tests/Arch/ArchTest.php` checks it: add a rule for each new domain module that gets tools.
- `laravel/mcp` is a dev dependency. Nothing outside these folders can load a class of the server.
- Never put a tool in a `ToolSearch` catalog: its `execute_tools` calls the tool without `RecordedCallTool`, so the call has no wide event and no `acting_as`.

## Tests

- Call the tool through the server, so the call runs `RecordedCallTool` and the discovery: `DevelopmentServer::tool(CreateTeamTool::class, [...])->assertOk()->assertStructuredContentPath(...)`.
- Test the result in the database (the action ran), the receipt (`mcpResult()` from `tests/Pest.php` returns the raw result, with the content and the structured content), one refusal, and the annotation when it is not the default for its kind.
- Test a resource with `DevelopmentServer::resource(TeamResource::class, ['team' => $team->id])`: what it shows, a secret that it never shows, and a missing record.
- A test of a module tool or resource goes in `app-modules/<module>/tests/Feature/Mcp/`.
