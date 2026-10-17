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
 * Pode rodar de novo quando quiser: as contas são as mesmas e o estado delas
 * volta ao começo.
 *
 *     php artisan db:seed --class="He4rt\Delas\Database\Seeders\DelasDemoSeeder"
 */
final class DelasDemoSeeder extends Seeder
{
    public const string PASSWORD = 'password';

    /**
     * Uma conta por situação. A chave vira o username (`delas.<chave>`).
     *
     * @var array<string, array{name: string, email: string, note: string}>
     */
    private const array PEOPLE = [
        'lider' => [
            'name' => 'Lia Líder',
            'email' => 'lider@delas.test',
            'note' => 'líder: tudo, mais Equipe, histórico completo e versões do motivo',
        ],
        'moderadora' => [
            'name' => 'Mia Moderadora',
            'email' => 'moderadora@delas.test',
            'note' => 'moderadora: fez os bloqueios e as rejeições abaixo',
        ],
        'moderadora2' => [
            'name' => 'Nina Moderadora',
            'email' => 'moderadora2@delas.test',
            'note' => 'outra moderadora: para testar "só os próprios"',
        ],
        'membra' => [
            'name' => 'Ana Membra',
            'email' => 'membra@delas.test',
            'note' => 'tem a tag',
        ],
        'pendente' => [
            'name' => 'Paula Pendente',
            'email' => 'pendente@delas.test',
            'note' => 'pedido pendente na Fila',
        ],
        'pedido2' => [
            'name' => 'Bruna Pedido',
            'email' => 'pedido2@delas.test',
            'note' => 'pedido pendente na Fila',
        ],
        'pedido3' => [
            'name' => 'Carla Pedido',
            'email' => 'pedido3@delas.test',
            'note' => 'pedido pendente na Fila',
        ],
        'recente' => [
            'name' => 'Rita Recente',
            'email' => 'recente@delas.test',
            'note' => 'rejeitada pela Mia há 1 hora: a Mia ainda edita o motivo',
        ],
        'espera' => [
            'name' => 'Eva Espera',
            'email' => 'espera@delas.test',
            'note' => 'rejeitada pela Mia há 3 dias: em espera, a Mia já não edita',
        ],
        'bloqueada' => [
            'name' => 'Bia Bloqueada',
            'email' => 'bloqueada@delas.test',
            'note' => 'bloqueada pela Mia',
        ],
        'bloqueada2' => [
            'name' => 'Gabi Bloqueada',
            'email' => 'bloqueada2@delas.test',
            'note' => 'bloqueada pela Nina',
        ],
        'nova' => [
            'name' => 'Nara Nova',
            'email' => 'nova@delas.test',
            'note' => 'nunca pediu: pode pedir a tag',
        ],
        'semdiscord' => [
            'name' => 'Sofia Sem Discord',
            'email' => 'semdiscord@delas.test',
            'note' => 'sem Discord conectado: precisa conectar antes de pedir',
        ],
    ];

    /**
     * Conta sem Discord conectado, para testar essa exigência do pedido.
     */
    private const string WITHOUT_DISCORD = 'semdiscord';

    /** @var array<string, User> */
    private array $people = [];

    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@admin.com')->first();

        if (!$admin instanceof User || !$admin->isSuperAdmin()) {
            $this->command->error('Rode o BaseSeeder antes: falta o super admin admin@admin.com.');

            return;
        }

        $this->createPeople();
        $this->resetDelasState();

        $this->at(now()->subDays(10), fn () => $this->formTeam($admin));
        $this->at(now()->subDays(6), fn () => $this->approveMember());
        $this->at(now()->subDays(3), fn () => $this->rejectAndBlockByMia());
        $this->at(now()->subDays(2), fn () => $this->blockByNina());
        $this->at(now()->subHour(), fn () => $this->rejectRecentlyByMia());
        $this->openPendingRequests();

        $this->guide();
    }

    /**
     * A líder recebe o papel do super admin e dá a tag e o papel às duas
     * moderadoras. Moderadora precisa ter a tag.
     */
    private function formTeam(User $admin): void
    {
        $lead = $this->person('lider');
        $lead->assignRole(UserRole::DelasLead);

        resolve(GrantDelasTag::class)->handle($lead, $admin, 'Liderança da He4rt Delas.');

        foreach (['moderadora', 'moderadora2'] as $key) {
            resolve(GrantDelasTag::class)->handle($this->person($key), $lead, 'Convidada para moderar.');
            resolve(AddDelasModerator::class)->handle($this->person($key), $lead);
        }
    }

    private function approveMember(): void
    {
        $request = resolve(RequestDelasTag::class)->handle($this->person('membra'));

        resolve(ApproveDelasRequest::class)->handle($request, $this->person('moderadora'));
    }

    /**
     * Há 3 dias: já passou o prazo de 24 horas para a Mia editar o motivo.
     */
    private function rejectAndBlockByMia(): void
    {
        $mia = $this->person('moderadora');

        $request = resolve(RequestDelasTag::class)->handle($this->person('espera'));
        resolve(RejectDelasRequest::class)->handle($request, $mia, 'Perfil sem informações suficientes.');

        resolve(BlockDelasRequester::class)->handle($this->person('bloqueada'), $mia, 'Pedidos repetidos para tumultuar a fila.');
    }

    /**
     * Bloqueio de outra moderadora: a Mia não consegue desfazer.
     */
    private function blockByNina(): void
    {
        resolve(BlockDelasRequester::class)->handle(
            $this->person('bloqueada2'),
            $this->person('moderadora2'),
            'Mensagens ofensivas no pedido.',
        );
    }

    /**
     * Há 1 hora, com um erro de digitação de propósito: a Mia ainda pode editar.
     */
    private function rejectRecentlyByMia(): void
    {
        $request = resolve(RequestDelasTag::class)->handle($this->person('recente'));

        resolve(RejectDelasRequest::class)->handle($request, $this->person('moderadora'), 'Perfil sem informaçoes suficientes.');
    }

    /**
     * Três pedidos na Fila, com 10 minutos entre eles.
     */
    private function openPendingRequests(): void
    {
        $minutesAgo = ['pendente' => 30, 'pedido2' => 20, 'pedido3' => 10];

        foreach ($minutesAgo as $key => $minutes) {
            $this->at(now()->subMinutes($minutes), fn () => resolve(RequestDelasTag::class)->handle($this->person($key)));
        }
    }

    /**
     * Cria as contas que ainda não existem, conecta o Discord e tira os papéis
     * da He4rt Delas, que os passos seguintes dão de novo.
     */
    private function createPeople(): void
    {
        foreach (self::PEOPLE as $key => $person) {
            $user = User::query()->where('email', $person['email'])->first()
                ?? User::factory()->create([
                    'name' => $person['name'],
                    'username' => 'delas.'.$key,
                    'email' => $person['email'],
                    'password' => Hash::make(self::PASSWORD),
                ]);

            if ($key !== self::WITHOUT_DISCORD) {
                $this->connectDiscord($user);
            }

            $user->removeRole(UserRole::DelasModerator);
            $user->removeRole(UserRole::DelasLead);

            $this->people[$key] = $user;
        }
    }

    private function connectDiscord(User $user): void
    {
        if (resolve(DelasEligibility::class)->hasDiscord($user)) {
            return;
        }

        ExternalIdentity::factory()->create([
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->getKey(),
            'provider' => IdentityProvider::Discord,
            'connected_by' => $user->getKey(),
        ]);
    }

    /**
     * Apaga o que a He4rt Delas sabe sobre as contas de demonstração, para o
     * estado voltar ao começo a cada execução.
     */
    private function resetDelasState(): void
    {
        $ids = array_map(fn (User $user): string => $user->getKey(), array_values($this->people));

        DelasTransition::query()->whereIn('user_id', $ids)->orWhereIn('actor_id', $ids)->delete();
        DelasRequesterBlock::query()->whereIn('user_id', $ids)->delete();
        DelasTagRequest::query()->whereIn('user_id', $ids)->delete();
    }

    /**
     * Sempre a versão atual da conta: os papéis mudam entre um passo e outro.
     */
    private function person(string $key): User
    {
        return $this->people[$key]->fresh() ?? $this->people[$key];
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
