<?php

declare(strict_types=1);

use Discord\Discord;
use Discord\Factory\Factory;
use Discord\Helpers\Collection;
use Discord\Http\Http;
use Discord\Parts\Channel\Message\Message;
use Discord\Parts\User\User;
use He4rt\BotDiscord\Events\GreetingsEvent;
use Mockery\MockInterface;

const GREETINGS_BOT_ID = '100000000000000001';
const GREETINGS_MEMBER_ID = '200000000000000002';
const GREETINGS_OTHER_BOT_ID = '300000000000000003';

function greetingsDiscord(): Discord
{
    $discord = Mockery::mock(Discord::class);
    $discord->shouldReceive('getHttpClient')->andReturn(Mockery::mock(Http::class));
    $discord->shouldReceive('getFactory')->andReturn(Mockery::mock(Factory::class));
    $discord->shouldReceive('getCollectionClass')->andReturn(Collection::class);

    $users = new Collection([
        GREETINGS_BOT_ID => new User($discord, ['id' => GREETINGS_BOT_ID, 'bot' => true]),
        GREETINGS_MEMBER_ID => new User($discord, ['id' => GREETINGS_MEMBER_ID, 'bot' => false]),
        GREETINGS_OTHER_BOT_ID => new User($discord, ['id' => GREETINGS_OTHER_BOT_ID, 'bot' => true]),
    ]);

    $client = (object) ['id' => GREETINGS_BOT_ID, 'users' => $users];
    new ReflectionProperty(Discord::class, 'client')->setValue($discord, $client);

    return $discord;
}

/**
 * @param  list<string>  $mentionedIds
 */
function greetingsMessage(Discord $discord, string $content, array $mentionedIds, string $authorId = GREETINGS_MEMBER_ID): Message&MockInterface
{
    return Mockery::mock(Message::class.'[reply]', [$discord, [
        'content' => $content,
        'author' => (object) ['id' => $authorId],
        'mentions' => array_map(fn (string $id): object => (object) ['id' => $id], $mentionedIds),
    ]]);
}

beforeEach(function (): void {
    $this->travelTo(now()->setTimezone(config('app.display_timezone'))->setTime(9, 0));
    $this->discord = greetingsDiscord();
});

it('greets a member who mentions the bot', function (): void {
    $message = greetingsMessage($this->discord, sprintf('<@%s> bom dia', GREETINGS_BOT_ID), [GREETINGS_BOT_ID]);
    $message->shouldReceive('reply')->once()->with('Bom dia!');

    GreetingsEvent::make()->handle($message, $this->discord);
});

it('greets a member who mentions nobody', function (): void {
    $message = greetingsMessage($this->discord, 'bom dia', []);
    $message->shouldReceive('reply')->once()->with('Bom dia!');

    GreetingsEvent::make()->handle($message, $this->discord);
});

it('ignores a greeting that does not mention the bot', function (): void {
    $message = greetingsMessage($this->discord, sprintf('<@%s> bom dia', GREETINGS_OTHER_BOT_ID), [GREETINGS_OTHER_BOT_ID]);
    $message->shouldNotReceive('reply');

    GreetingsEvent::make()->handle($message, $this->discord);
});

it('ignores a greeting sent by another bot', function (): void {
    $message = greetingsMessage($this->discord, sprintf('<@%s> bom dia', GREETINGS_BOT_ID), [GREETINGS_BOT_ID], GREETINGS_OTHER_BOT_ID);
    $message->shouldNotReceive('reply');

    GreetingsEvent::make()->handle($message, $this->discord);
});
