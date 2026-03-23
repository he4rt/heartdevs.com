<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const array JUNK_KEYWORDS = [
        'não', 'nao', 'tenho', 'possuo', 'ainda', 'lembro',
        'sem ', 'sem.', 'nenhum', 'none', 'nope', 'null', 'undefined',
        'n/a', 'n/d', 'cancelar', 'continuar', 'ignore', 'exit', 'pular',
        'desconhecido', 'indisponivel', 'indisponível',
        'privado', 'private', 'secret', 'segredo', 'prefiro',
        'depois', 'esqueci', 'preguiça', 'sorry', 'desculpa',
        'iniciante', 'construção', 'manutenção',
        'não sei', 'nao sei', 'no momento', 'por enquanto',
        'vou criar', 'vou fazer', 'vou ficar',
        'tenho q', 'fiz ainda', 'criei ainda',
        'sei nem', 'sei pra',
        'não uso', 'nao uso', 'não utilizo',
        'estou sem', 'fodase', 'fica pra',
    ];

    private const array JUNK_EXACT = [
        '.', '..', '...', '(...)', '-', '--', '-x-',
        'a', 'x', 'xx', 'xxxx', 'd', 'dd', 'i', 'n', 'nn', 's', 't',
        'no', 'non', 'nop', 'nt', 'we', 'af', 'sa', 'lk',
        '0', '00', '1', '?', '??', ';', '_', '~',
        'git', 'github', 'github.com', 'sem', 'nada', 'keine',
        'oi', 'dope', 'scarlet', 'eew', 'cat.jsx',
        '⛔', '🐰', '🥸', '—', '…',
    ];

    public function up(): void
    {
        $rows = DB::table('user_information')
            ->whereNotNull('github_url')
            ->where('github_url', '!=', '')
            ->whereRaw("NOT (github_url LIKE 'https://github.com/%' AND LENGTH(github_url) > 19)")
            ->get(['id', 'github_url']);

        foreach ($rows as $row) {
            $lower = mb_strtolower(mb_trim($row->github_url));

            if ($this->isJunk($lower)) {
                DB::table('user_information')->where('id', $row->id)->update(['github_url' => null]);

                continue;
            }

            $fixed = $this->tryFixUrl($lower);

            if ($fixed !== null) {
                DB::table('user_information')->where('id', $row->id)->update(['github_url' => $fixed]);

                continue;
            }

            // Non-github links, gifs, random URLs — null them out
            DB::table('user_information')->where('id', $row->id)->update(['github_url' => null]);
        }
    }

    public function down(): void
    {
        // Irreversible: original junk values are not preserved.
    }

    private function isJunk(string $lower): bool
    {
        if (in_array($lower, self::JUNK_EXACT, true)) {
            return true;
        }

        foreach (self::JUNK_KEYWORDS as $keyword) {
            if (str_contains($lower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function tryFixUrl(string $lower): ?string
    {
        if (preg_match('#^github\.com/[\w.\-]+#', $lower)) {
            return 'https://'.$lower;
        }

        if (preg_match('#^(https?://)?www\.github\.com/([\w.\-]+)#i', $lower, $m)) {
            return 'https://github.com/'.$m[2];
        }

        if (preg_match('#^https?://git[Hh]ub\.com/([\w.\-]+)#', $lower, $m)) {
            return 'https://github.com/'.$m[1];
        }

        if (preg_match('#^https?//github\.com/([\w.\-]+)#', $lower, $m)) {
            return 'https://github.com/'.$m[1];
        }

        if (preg_match('#^https?;//github\.com/([\w.\-]+)#', $lower, $m)) {
            return 'https://github.com/'.$m[1];
        }

        if (preg_match('#^https?:+//github\.com/([\w.\-]+)#', $lower, $m)) {
            return 'https://github.com/'.$m[1];
        }

        if (preg_match('#^h\w{3,6}s?[:/]+/*github\.com/([\w.\-]+)#', $lower, $m)) {
            return 'https://github.com/'.$m[1];
        }

        if (preg_match('#^https?://(?:gi[th]*ub|gu[th]*ub|githubm)\.com/([\w.\-]+)#', $lower, $m)) {
            return 'https://github.com/'.$m[1];
        }

        if (preg_match('#^https?://github,com/([\w.\-]+)#', $lower, $m)) {
            return 'https://github.com/'.$m[1];
        }

        return null;
    }
};
