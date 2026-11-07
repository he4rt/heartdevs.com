<?php

declare(strict_types=1);

use He4rt\IntegrationSlido\Data\SlidoEventHash;

test('extrai o hash do evento do link ou do próprio hash', function (string $input, ?string $expected): void {
    expect(SlidoEventHash::fromInput($input))->toBe($expected);
})->with([
    'link do participante' => ['https://app.sli.do/event/jC9L7Lu93Vw3ssNhB92Pvb', 'jC9L7Lu93Vw3ssNhB92Pvb'],
    'link do wall com query' => ['https://wall.sli.do/event/jC9L7Lu93Vw3ssNhB92Pvb?section=abc', 'jC9L7Lu93Vw3ssNhB92Pvb'],
    'link do admin com caminho' => ['https://admin.sli.do/event/jC9L7Lu93Vw3ssNhB92Pvb/questions', 'jC9L7Lu93Vw3ssNhB92Pvb'],
    'hash com espaços' => ['  jC9L7Lu93Vw3ssNhB92Pvb ', 'jC9L7Lu93Vw3ssNhB92Pvb'],
    'outro domínio' => ['https://example.com/event/jC9L7Lu93Vw3ssNhB92Pvb', null],
    'http sem tls' => ['http://app.sli.do/event/jC9L7Lu93Vw3ssNhB92Pvb', null],
    'texto qualquer' => ['he4rt pub', null],
]);
