<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Actions\Delas;

use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use He4rt\Delas\TagRequest\Queries\DelasEligibility;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;

/**
 * "Ver perfil": um resumo só leitura, na lateral, com o que a moderação precisa
 * para decidir e nada além (sem e-mail, data de nascimento nem localização).
 *
 * A linha da tabela pode ser a própria pessoa (Equipe) ou algo ligado a ela por
 * uma relação (`user` na Fila, em Membras, Bloqueios e Histórico).
 */
final class ViewDelasProfileAction extends Action
{
    private ?string $personRelation = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->modalHeading(__('panel-admin::delas.profile.heading'))
            ->slideOver()
            ->modalWidth(Width::Medium)
            ->modalContent(function (Model $record): View {
                $person = $this->personOf($record);

                return view('panel-admin::moderation.delas.person-profile', [
                    'person' => $person,
                    'eligibility' => resolve(DelasEligibility::class)->for($person),
                    'discord' => $this->connectedDiscord($person),
                ]);
            })
            ->modalSubmitAction(action: false)
            ->modalCancelActionLabel(__('panel-admin::delas.actions.close'));
    }

    public static function getDefaultName(): string
    {
        return 'viewProfile';
    }

    public function personRelation(?string $relation): static
    {
        $this->personRelation = $relation;

        return $this;
    }

    /**
     * O Discord que a pessoa conectou e ainda está conectado, o mais recente.
     * As conexões já vêm carregadas com a pessoa.
     */
    private function connectedDiscord(User $person): ?ExternalIdentity
    {
        return $person->providers
            ->where('provider', IdentityProvider::Discord)
            ->filter(fn (ExternalIdentity $identity): bool => $identity->isConnected())
            ->sortByDesc('connected_at')
            ->first();
    }

    /**
     * A pessoa é carregada sozinha, com o perfil e as conexões, só quando o
     * resumo abre: a tabela não paga por isso em cada linha.
     */
    private function personOf(Model $record): User
    {
        $id = $this->personRelation === null
            ? $record->getKey()
            : $record->getAttribute($this->personRelation.'_id');

        return User::query()->with(['profile', 'providers'])->whereKey($id)->firstOrFail();
    }
}
