<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\User\Enums\UserSituation;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationDiscord\Models\DiscordMember;
use He4rt\Moderation\Cases\Models\ModerationCase;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Conta')
                    ->icon(Heroicon::OutlinedUser)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('username')
                            ->label('Username')
                            ->copyable(),

                        TextEntry::make('name')
                            ->label('Nome'),

                        TextEntry::make('email')
                            ->label('E-mail')
                            ->copyable()
                            ->placeholder('Sem e-mail'),

                        IconEntry::make('is_donator')
                            ->label('Apoiador')
                            ->boolean(),

                        TextEntry::make('roles.name')
                            ->label('Papéis')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => UserRole::from($state)->getLabel())
                            ->color(fn (string $state): array => UserRole::from($state)->getColor())
                            ->placeholder('Nenhum'),

                        TextEntry::make('first_login_at')
                            ->label('Primeiro login')
                            ->dateTime('d/m/Y H:i')
                            ->timezone(config('app.display_timezone'))
                            ->placeholder('Nunca'),

                        TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime('d/m/Y H:i')
                            ->timezone(config('app.display_timezone')),
                    ]),

                Section::make('Situação')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->description('Somente leitura. Punições são aplicadas pelo fluxo de moderação.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('situation')
                            ->label('Situação')
                            ->badge()
                            ->state(fn (User $record): UserSituation => $record->situation),

                        TextEntry::make('suspended_until')
                            ->label('Suspenso até')
                            ->dateTime('d/m/Y H:i')
                            ->timezone(config('app.display_timezone'))
                            ->placeholder('—'),

                        TextEntry::make('banned_at')
                            ->label('Banido em')
                            ->dateTime('d/m/Y H:i')
                            ->timezone(config('app.display_timezone'))
                            ->placeholder('—'),
                    ]),

                Section::make('Perfil')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('profile.headline')
                            ->label('Headline')
                            ->placeholder('Não preenchido'),

                        TextEntry::make('profile.seniority_level')
                            ->label('Senioridade')
                            ->badge()
                            ->placeholder('—'),

                        IconEntry::make('profile.available_for_proposals')
                            ->label('Aberto a propostas')
                            ->boolean(),
                    ]),

                Section::make('Gamificação')
                    ->icon(Heroicon::OutlinedTrophy)
                    ->description('Somente leitura. Valores derivados não são ajustáveis pelo painel.')
                    ->collapsible()
                    ->columns(4)
                    ->schema([
                        TextEntry::make('character.level')
                            ->label('Nível')
                            ->placeholder('—'),

                        TextEntry::make('character.experience')
                            ->label('XP')
                            ->placeholder('—'),

                        TextEntry::make('character.reputation')
                            ->label('Reputação')
                            ->placeholder('—'),

                        TextEntry::make('wallet_balance')
                            ->label('Carteira')
                            ->state(fn (User $record): ?int => $record->character?->wallet()?->balance)
                            ->placeholder('—'),

                        TextEntry::make('badges')
                            ->label('Badges')
                            ->state(fn (User $record): string => (string) $record->character?->badges->pluck('name')->implode(', '))
                            ->placeholder('Nenhuma')
                            ->columnSpanFull(),
                    ]),

                Section::make('Atividade')
                    ->icon(Heroicon::OutlinedSignal)
                    ->collapsible()
                    ->schema([
                        RepeatableEntry::make('providers')
                            ->label('Conexões')
                            ->columns(4)
                            ->schema([
                                TextEntry::make('provider')
                                    ->label('Provider')
                                    ->badge(),

                                TextEntry::make('external_account_id')
                                    ->label('Conta'),

                                TextEntry::make('connected_at')
                                    ->label('Conectado em')
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('—'),

                                TextEntry::make('messages_count')
                                    ->label('Mensagens'),
                            ])
                            ->placeholder('Nenhuma identidade conectada'),

                        TextEntry::make('discord_roles')
                            ->label('Roles do Discord')
                            ->state(fn (User $record): string => self::discordRoles($record))
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),

                Section::make('Moderação')
                    ->icon(Heroicon::OutlinedShieldExclamation)
                    ->description('Somente leitura. Casos são abertos e resolvidos pelo fluxo de moderação.')
                    ->collapsible()
                    ->columns(2)
                    ->visible(fn (): bool => auth()->user()?->canViewModeration() ?? false)
                    ->schema([
                        TextEntry::make('moderation_cases_as_author')
                            ->label('Casos como autor')
                            ->state(fn (User $record): int => ModerationCase::query()->where('author_id', $record->id)->count()),

                        TextEntry::make('moderation_cases_as_assignee')
                            ->label('Casos como responsável')
                            ->state(fn (User $record): int => ModerationCase::query()->where('assigned_to', $record->id)->count()),
                    ]),
            ]);
    }

    private static function discordRoles(User $record): string
    {
        $discordIdentity = $record->providers
            ->firstWhere('provider', IdentityProvider::Discord);

        if ($discordIdentity === null) {
            return '';
        }

        $roles = DiscordMember::query()
            ->where('external_identity_id', $discordIdentity->id)
            ->with('roles')
            ->first()
            ?->roles;

        return (string) $roles?->pluck('name')->implode(', ');
    }
}
