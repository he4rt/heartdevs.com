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
        'omitir', 'criando', 'not found',
    ];

    private const array JUNK_EXACT = [
        '.', '..', '...', '(...)', '-', '--', '-x-',
        'a', 'x', 'xx', 'xxxx', 'd', 'dd', 'i', 'n', 'nn', 's', 't',
        'no', 'non', 'nop', 'nt', 'we', 'af', 'sa', 'lk',
        '0', '00', '1', '?', '??', ';', '_', '~',
        'sem', 'nada', 'keine', 'oi', 'dope',
        '⛔', '🐰', '🥸', '—', '…',
    ];

    public function up(): void
    {
        // Phase 1: Clean junk + fix malformed URLs
        $rows = DB::table('user_information')
            ->whereNotNull('linkedin_url')
            ->where('linkedin_url', '!=', '')
            ->whereRaw("NOT (linkedin_url LIKE 'https://linkedin.com/in/%')")
            ->whereRaw("NOT (linkedin_url LIKE 'https://www.linkedin.com/in/%')")
            ->get(['id', 'linkedin_url']);

        foreach ($rows as $row) {
            $lower = mb_strtolower(mb_trim($row->linkedin_url));

            if ($this->isJunk($lower)) {
                DB::table('user_information')->where('id', $row->id)->update(['linkedin_url' => null]);

                continue;
            }

            $fixed = $this->tryFixUrl($lower);

            if ($fixed !== null) {
                DB::table('user_information')->where('id', $row->id)->update(['linkedin_url' => $fixed]);

                continue;
            }

            DB::table('user_information')->where('id', $row->id)->update(['linkedin_url' => null]);
        }

        // Phase 2: Normalize www → no www + strip trailing slash
        $wwwRows = DB::table('user_information')
            ->whereNotNull('linkedin_url')
            ->where('linkedin_url', 'LIKE', 'https://www.linkedin.com/in/%')
            ->get(['id', 'linkedin_url']);

        foreach ($wwwRows as $row) {
            $normalized = str_replace('https://www.linkedin.com/in/', 'https://linkedin.com/in/', $row->linkedin_url);
            $normalized = mb_rtrim($normalized, '/');

            DB::table('user_information')->where('id', $row->id)->update(['linkedin_url' => $normalized]);
        }

        // Phase 3: Strip trailing slash from already-correct URLs
        $trailingSlash = DB::table('user_information')
            ->whereNotNull('linkedin_url')
            ->where('linkedin_url', 'LIKE', '%/')
            ->get(['id', 'linkedin_url']);

        foreach ($trailingSlash as $row) {
            DB::table('user_information')->where('id', $row->id)->update([
                'linkedin_url' => mb_rtrim($row->linkedin_url, '/'),
            ]);
        }

        // Phase 4: Null bare URL without handle
        DB::table('user_information')
            ->where('linkedin_url', 'https://linkedin.com/in')
            ->update(['linkedin_url' => null]);
    }

    public function down(): void
    {
        // Irreversible
    }

    private function isJunk(string $lower): bool
    {
        if (in_array($lower, self::JUNK_EXACT, strict: true)) {
            return true;
        }

        return array_any(self::JUNK_KEYWORDS, fn (string $keyword) => str_contains($lower, $keyword));
    }

    private function tryFixUrl(string $lower): ?string
    {
        if (preg_match('#linkedin\.com/in\s*/?([\w\-%.]+)#', $lower, $m)) {
            return 'https://linkedin.com/in/'.mb_rtrim($m[1], '/');
        }

        if (preg_match('#linkedin\.com/mwlite/in/([\w\-%.]+)#', $lower, $m)) {
            return 'https://linkedin.com/in/'.mb_rtrim($m[1], '/');
        }

        if (preg_match('#^https?://(?:www\.)?linkedin\.com/([\w\-]{3,})$#', $lower, $m)) {
            $path = $m[1];
            if (!in_array($path, ['feed', 'jobs', 'company', 'me', 'messaging', 'notifications', 'search'], strict: true)) {
                return 'https://linkedin.com/in/'.$path;
            }
        }

        if (preg_match('#^(?:www\.)?linkedin\.com/in/([\w\-%.]+)#', $lower, $m)) {
            return 'https://linkedin.com/in/'.mb_rtrim($m[1], '/');
        }

        return null;
    }
};
