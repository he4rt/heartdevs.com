<?php

declare(strict_types=1);

namespace He4rt\Delas\Database\Seeders;

use Carbon\CarbonInterface;
use He4rt\Delas\Block\Actions\BlockDelasRequester;
use He4rt\Delas\Block\Models\DelasRequesterBlock;
use He4rt\Delas\History\Models\DelasTransition;
use He4rt\Delas\TagRequest\Actions\ApproveDelasRequest;
use He4rt\Delas\TagRequest\Actions\GrantDelasTag;
use He4rt\Delas\TagRequest\Actions\RejectDelasRequest;
use He4rt\Delas\TagRequest\Actions\RequestDelasTag;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Delas\TagRequest\Queries\DelasEligibility;
use He4rt\Delas\Team\Actions\AddDelasModerator;
use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;

/**
 * Contas de demonstração para testar a He4rt Delas na mão: uma por papel e por
 * situação (pendente, em espera, bloqueada...). O histórico sai das próprias
 * actions de domínio, então as telas mostram o comportamento real.
 *
 * Roda de novo quando quiser: as contas são as mesmas e o estado delas volta
 * ao começo.
 *
 *     php artisan db:seed --class="He4rt\Delas\Database\Seeders\DelasDemoSeeder"
 */
final class DelasDemoSeeder extends Seeder
{
    public const string PASSWORD = 'password';

    /**
     * @var array<string, array{name: string, email: string, note: string}>
     */
    private const array PEOPLE = [
        'lider' => ['name' => 'Lia Líder', 'email' => 'lider@delas.test', 'note' => 'líder: tudo, mais Equipe, histórico completo e versões do motivo'],
        'moderadora' => ['name' => 'Mia Moderadora', 'email' => 'moderadora@delas.test', 'note' => 'moderadora: fez os bloqueios e as rejeições abaixo'],
        'moderadora2' => ['name' => 'Nina Moderadora', 'email' => 'moderadora2@delas.test', 'note' => 'outra moderadora: para testar "só os próprios"'],
        'membra' => ['name' => 'Ana Membra', 'email' => 'membra@delas.test', 'note' => 'tem a tag'],
        'pendente' => ['name' => 'Paula Pendente', 'email' => 'pendente@delas.test', 'note' => 'pedido pendente na Fila'],
        'pedido2' => ['name' => 'Bruna Pedido', 'email' => 'pedido2@delas.test', 'note' => 'pedido pendente na Fila'],
        'pedido3' => ['name' => 'Carla Pedido', 'email' => 'pedido3@delas.test', 'note' => 'pedido pendente na Fila'],
        'recente' => ['name' => 'Rita Recente', 'email' => 'recente@delas.test', 'note' => 'rejeitada pela Mia há 1 hora: a Mia ainda edita o motivo'],
        'espera' => ['name' => 'Eva Espera', 'email' => 'espera@delas.test', 'note' => 'rejeitada pela Mia há 3 dias: em espera, a Mia já não edita'],
        'bloqueada' => ['name' => 'Bia Bloqueada', 'email' => 'bloqueada@delas.test', 'note' => 'bloqueada pela Mia'],
        'bloqueada2' => ['name' => 'Gabi Bloqueada', 'email' => 'bloqueada2@delas.test', 'note' => 'bloqueada pela Nina'],
        'nova' => ['name' => 'Nara Nova', 'email' => 'nova@delas.test', 'note' => 'nunca pediu: pode pedir a tag'],
        'semdiscord' => ['name' => 'Sofia Sem Discord', 'email' => 'semdiscord@delas.test', 'note' => 'sem Discord conectado: precisa conectar antes de pedir'],
    ];

    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@admin.com')->first();

        if (!$admin instanceof User || !$admin->isSuperAdmin()) {
            $this->command->error('Rode o BaseSeeder antes: falta o super admin admin@admin.com.');

            return;
        }

        $people = collect(self::PEOPLE)->map(fn (array $person, string $key): User => $this->person($key, $person));

        $this->resetDelasState($people->all());

        $lead = $people['lider'];
        $mia = $people['moderadora'];
        $nina = $people['moderadora2'];

        $this->at(now()->subDays(10), function () use ($admin, $lead, $mia, $nina): void {
            $lead->assignRole(UserRole::DelasLead);
            resolve(GrantDelasTag::class)->handle($lead, $admin, 'Liderança da He4rt Delas.');
            resolve(GrantDelasTag::class)->handle($mia, $lead, 'Convidada para moderar.');
            resolve(GrantDelasTag::class)->handle($nina, $lead, 'Convidada para moderar.');
            resolve(AddDelasModerator::class)->handle($mia->fresh(), $lead);
            resolve(AddDelasModerator::class)->handle($nina->fresh(), $lead);
        });

        $mia = $mia->fresh();
        $nina = $nina->fresh();

        $this->at(now()->subDays(6), fn () => resolve(ApproveDelasRequest::class)->handle(
            resolve(RequestDelasTag::class)->handle($people['membra']),
            $mia,
        ));

        $this->at(now()->subDays(3), function () use ($people, $mia): void {
            resolve(RejectDelasRequest::class)->handle(
                resolve(RequestDelasTag::class)->handle($people['espera']),
                $mia,
                'Perfil sem informações suficientes.',
            );
            resolve(BlockDelasRequester::class)->handle($people['bloqueada'], $mia, 'Pedidos repetidos para tumultuar a fila.');
        });

        $this->at(now()->subDays(2), fn () => resolve(BlockDelasRequester::class)->handle(
            $people['bloqueada2'],
            $nina,
            'Mensagens ofensivas no pedido.',
        ));

        $this->at(now()->subHour(), fn () => resolve(RejectDelasRequest::class)->handle(
            resolve(RequestDelasTag::class)->handle($people['recente']),
            $mia,
            'Perfil sem informaçoes suficientes.',
        ));

        foreach (['pendente' => 30, 'pedido2' => 20, 'pedido3' => 10] as $key => $minutesAgo) {
            $this->at(now()->subMinutes($minutesAgo), fn () => resolve(RequestDelasTag::class)->handle($people[$key]));
        }

        $this->guide();
    }

    /**
     * @param  array{name: string, email: string, note: string}  $person
     */
    private function person(string $key, array $person): User
    {
        $user = User::query()->where('email', $person['email'])->first()
            ?? User::factory()->create([
                'name' => $person['name'],
                'username' => 'delas.'.$key,
                'email' => $person['email'],
                'password' => Hash::make(self::PASSWORD),
            ]);

        // Todas têm o Discord conectado, menos a Sofia, que serve para testar essa exigência.
        if ($key !== 'semdiscord' && !resolve(DelasEligibility::class)->hasDiscord($user)) {
            ExternalIdentity::factory()->create([
                'model_type' => $user->getMorphClass(),
                'model_id' => $user->getKey(),
                'provider' => IdentityProvider::Discord,
                'connected_by' => $user->getKey(),
            ]);
        }

        $user->removeRole(UserRole::DelasModerator);
        $user->removeRole(UserRole::DelasLead);

        return $user->fresh() ?? $user;
    }

    /**
     * Apaga o que a He4rt Delas sabe sobre as contas de demonstração, para o
     * estado voltar ao começo a cada execução.
     *
     * @param  array<string, User>  $people
     */
    private function resetDelasState(array $people): void
    {
        $ids = collect($people)->map(fn (User $user): string => $user->getKey())->values()->all();

        DelasTransition::query()->whereIn('user_id', $ids)->orWhereIn('actor_id', $ids)->delete();
        DelasRequesterBlock::query()->whereIn('user_id', $ids)->delete();
        DelasTagRequest::query()->whereIn('user_id', $ids)->delete();
    }

    /**
     * Roda um passo como se fosse aquele momento, para o histórico e a espera
     * terem datas de verdade.
     */
    private function at(CarbonInterface $moment, callable $step): void
    {
        Date::setTestNow($moment);

        try {
            $step();
        } finally {
            Date::setTestNow();
        }
    }

    private function guide(): void
    {
        $output = $this->command->getOutput();

        $output->writeln('');
        $output->writeln('<options=bold>Demonstração da He4rt Delas</> <fg=gray>· /app (perfil) e /admin/mod/delas</>');
        $output->writeln('');

        foreach (self::PEOPLE as $person) {
            $output->writeln(sprintf('<fg=cyan>%s</> <fg=gray>%s</>', mb_str_pad($person['email'], 24), $person['note']));
        }

        $output->writeln('');
        $output->writeln(sprintf('<fg=gray>Senha de todas:</> %s <fg=gray>· super admin: admin@admin.com / admin</>', self::PASSWORD));
        $output->writeln('');
    }
}
