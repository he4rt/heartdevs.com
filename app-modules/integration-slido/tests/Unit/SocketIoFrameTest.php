<?php

declare(strict_types=1);

use He4rt\IntegrationSlido\Stream\FrameKind;
use He4rt\IntegrationSlido\Stream\SocketIoFrame;

test('reconhece cada tipo de frame do socket.io v4', function (string $raw, FrameKind $expected): void {
    expect(SocketIoFrame::parse($raw)->kind)->toBe($expected);
})->with([
    'open' => ['0{"sid":"abc","pingInterval":25000,"pingTimeout":20000}', FrameKind::Open],
    'connected' => ['40{"sid":"def"}', FrameKind::Connected],
    'ping' => ['2', FrameKind::Ping],
    'ack' => ['430[{"code":200,"message":"Subscribed"}]', FrameKind::Ack],
    'event' => ['42["newQuestion",{"params":{}}]', FrameKind::Event],
    'connect error' => ['44{"message":"unauthorized"}', FrameKind::ConnectError],
    'disconnect' => ['41', FrameKind::Disconnected],
    'json quebrado' => ['42[not-json', FrameKind::Unknown],
]);

test('lê o nome e os params de um evento', function (): void {
    $frame = SocketIoFrame::parse('42["slido.event.question.update",{"target":"slido/events/x/","params":{"event_question_id":7}}]');

    expect($frame->eventName)->toBe('slido.event.question.update')
        ->and($frame->params())->toBe(['event_question_id' => 7]);
});

test('aceita a assinatura só quando o ack tem code 200', function (): void {
    expect(SocketIoFrame::parse('430[{"code":200,"message":"Subscribed"}]')->isSubscriptionAccepted())->toBeTrue()
        ->and(SocketIoFrame::parse('430[{"code":401,"message":"Unauthorized"}]')->isSubscriptionAccepted())->toBeFalse();
});

test('monta o frame de subscribe com ack id 0', function (): void {
    expect(SocketIoFrame::subscribe('token-1', 'slido/events/abc/'))
        ->toBe('420["subscribe",{"accessToken":"token-1","targets":["slido/events/abc/"]}]');
});

test('lê o evento mesmo quando o servidor manda um id de ack antes do pacote', function (): void {
    $frame = SocketIoFrame::parse('4212["newQuestion",{"params":{"event_question_id":5}}]');

    expect($frame->kind)->toBe(FrameKind::Event)
        ->and($frame->eventName)->toBe('newQuestion')
        ->and($frame->params())->toBe(['event_question_id' => 5]);
});
