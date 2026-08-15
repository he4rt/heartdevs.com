<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use He4rt\Activity\Voice\Models\Voice;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationDiscord\Models\DiscordMember;
use He4rt\IntegrationDiscord\Models\DiscordRole;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Profile\Enums\SocialPlatform;
use Illuminate\Database\Eloquent\Collection;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('User')
                    ->tabs([
                        Tab::make('Identidade')
                            ->schema(self::identitySchema()),

                        Tab::make('Perfil')
                            ->schema(self::profileSchema()),

                        Tab::make('Endereço')
                            ->schema(self::addressSchema()),

                        Tab::make('Gamificação')
                            ->schema(self::gamificationSchema()),

                        Tab::make('Atividade')
                            ->schema(self::activitySchema()),

                        Tab::make('Moderação')
                            ->schema(self::moderationSchema())
                            ->hidden(static fn (): bool => !(auth()->user()?->can('update', User::class) ?? false)),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, Component>
     */
    private static function identitySchema(): array
    {
        return [
            TextEntry::make('username')
                ->label('Username'),

            TextEntry::make('name')
                ->label('Name'),

            TextEntry::make('email')
                ->label('Email')
                ->placeholder('—'),

            TextEntry::make('role')
                ->label('Role')
                ->badge(),

            IconEntry::make('is_donator')
                ->label('Donator')
                ->boolean(),

            TextEntry::make('created_at')
                ->label('Membro desde')
                ->dateTime(),
        ];
    }

    /**
     * @return array<int, Component>
     */
    private static function profileSchema(): array
    {
        return [
            TextEntry::make('profile.nickname')
                ->label('Nickname')
                ->placeholder('—'),

            TextEntry::make('profile.headline')
                ->label('Headline')
                ->placeholder('—'),

            TextEntry::make('profile.about')
                ->label('Sobre')
                ->placeholder('—')
                ->columnSpanFull(),

            TextEntry::make('profile.birthdate')
                ->label('Data de Nascimento')
                ->date()
                ->placeholder('—'),

            TextEntry::make('profile.seniority_level')
                ->label('Senioridade')
                ->badge()
                ->placeholder('—'),

            TextEntry::make('profile.years_experience')
                ->label('Anos de Experiência')
                ->placeholder('—'),

            IconEntry::make('profile.available_for_proposals')
                ->label('Disponível para Propostas')
                ->boolean(),

            TextEntry::make('profile.start_availability')
                ->label('Disponibilidade de Início')
                ->badge()
                ->placeholder('—'),

            TextEntry::make('profile.expected_salary_min')
                ->label('Pretensão Salarial Mínima')
                ->money('BRL')
                ->placeholder('—'),

            TextEntry::make('profile.expected_salary_max')
                ->label('Pretensão Salarial Máxima')
                ->money('BRL')
                ->placeholder('—'),

            TextEntry::make('profile.social_links.'.SocialPlatform::Instagram->value)
                ->label('Instagram')
                ->placeholder('—'),

            TextEntry::make('profile.social_links.'.SocialPlatform::Twitter->value)
                ->label('Twitter')
                ->placeholder('—'),

            TextEntry::make('profile.social_links.'.SocialPlatform::Website->value)
                ->label('Website')
                ->placeholder('—'),

            TextEntry::make('profile.social_links.'.SocialPlatform::YouTube->value)
                ->label('YouTube')
                ->placeholder('—'),

            TextEntry::make('profile.social_links.'.SocialPlatform::Bluesky->value)
                ->label('Bluesky')
                ->placeholder('—'),

            IconEntry::make('profile.preferences.hasDisability')
                ->label('Possui Deficiência')
                ->boolean(),

            IconEntry::make('profile.preferences.willingToRelocate')
                ->label('Disponível para Relocação')
                ->boolean(),

            IconEntry::make('profile.preferences.isOpenToRemote')
                ->label('Aberto a Trabalho Remoto')
                ->boolean(),

            TextEntry::make('profile.preferences.employmentTypes')
                ->label('Tipos de Contratação')
                ->badge()
                ->placeholder('—'),
        ];
    }

    /**
     * @return array<int, Component>
     */
    private static function addressSchema(): array
    {
        return [
            TextEntry::make('address.country')
                ->label('País')
                ->placeholder('—'),

            TextEntry::make('address.state')
                ->label('Estado')
                ->placeholder('—'),

            TextEntry::make('address.city')
                ->label('Cidade')
                ->placeholder('—'),

            TextEntry::make('address.zip_code')
                ->label('CEP')
                ->placeholder('—'),
        ];
    }

    /**
     * @return array<int, Component>
     */
    private static function gamificationSchema(): array
    {
        return [
            TextEntry::make('character.level')
                ->label('Nível')
                ->badge()
                ->placeholder('—'),

            TextEntry::make('character.experience')
                ->label('Experiência')
                ->placeholder('—'),

            TextEntry::make('character.reputation')
                ->label('Reputação')
                ->placeholder('—'),

            RepeatableEntry::make('character.badges')
                ->label('Badges')
                ->schema([
                    TextEntry::make('name')
                        ->label('Nome'),

                    TextEntry::make('pivot.claimed_at')
                        ->label('Conquistada em')
                        ->dateTime(),
                ])
                ->columns(2)
                ->columnSpanFull(),

            RepeatableEntry::make('character.wallets')
                ->label('Carteira')
                ->schema([
                    TextEntry::make('currency')
                        ->label('Moeda'),

                    TextEntry::make('balance')
                        ->label('Saldo'),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, Component>
     */
    private static function activitySchema(): array
    {
        return [
            RepeatableEntry::make('providers')
                ->label('Conexões')
                ->schema([
                    TextEntry::make('provider')
                        ->label('Provider')
                        ->badge(),

                    TextEntry::make('external_account_id')
                        ->label('ID Externo')
                        ->placeholder('—'),

                    TextEntry::make('connected_at')
                        ->label('Conectado em')
                        ->dateTime()
                        ->placeholder('—'),

                    TextEntry::make('messages_count')
                        ->label('Mensagens'),
                ])
                ->columns(4)
                ->columnSpanFull(),

            TextEntry::make('activity_messages_total')
                ->label('Total de Mensagens')
                ->state(static fn (User $record): int => self::messagesCount($record)),

            TextEntry::make('activity_voice_hours')
                ->label('Horas de Voice (aproximado)')
                ->state(static fn (User $record): float => self::voiceHours($record)),

            TextEntry::make('activity_discord_roles')
                ->label('Cargos no Discord')
                ->state(static fn (User $record): array => self::discordRoles($record))
                ->badge()
                ->placeholder('—'),
        ];
    }

    /**
     * @return array<int, Component>
     */
    private static function moderationSchema(): array
    {
        $caseSchema = [
            TextEntry::make('status')
                ->label('Status')
                ->badge(),

            TextEntry::make('severity')
                ->label('Severidade')
                ->badge()
                ->placeholder('—'),

            TextEntry::make('violation_type')
                ->label('Tipo de Violação')
                ->badge()
                ->placeholder('—'),

            TextEntry::make('created_at')
                ->label('Criado em')
                ->dateTime(),
        ];

        return [
            RepeatableEntry::make('moderation_authored_cases')
                ->label('Casos como Autor')
                ->state(static fn (User $record) => ModerationCase::query()
                    ->where('author_id', $record->id)
                    ->latest()
                    ->limit(10)
                    ->get())
                ->schema($caseSchema)
                ->columns(4)
                ->columnSpanFull(),

            RepeatableEntry::make('moderation_assigned_cases')
                ->label('Casos como Responsável')
                ->state(static fn (User $record) => ModerationCase::query()
                    ->where('assigned_to', $record->id)
                    ->latest()
                    ->limit(10)
                    ->get())
                ->schema($caseSchema)
                ->columns(4)
                ->columnSpanFull(),

            TextEntry::make('suspended_until')
                ->label('Suspenso até')
                ->dateTime()
                ->placeholder('—'),

            TextEntry::make('banned_at')
                ->label('Banido em')
                ->dateTime()
                ->placeholder('—'),
        ];
    }

    private static function messagesCount(User $record): int
    {
        /** @var Collection<int, ExternalIdentity> $providers */
        $providers = $record->providers;

        return $providers->sum(static fn (ExternalIdentity $identity): int => $identity->messages_count);
    }

    private static function voiceHours(User $record): float
    {
        $identityIds = $record->providers->pluck('id');

        if ($identityIds->isEmpty()) {
            return 0.0;
        }

        $joinedCount = Voice::query()
            ->whereIn('external_identity_id', $identityIds)
            ->where('state', 'joined')
            ->count();

        return round($joinedCount * 0.75, 1);
    }

    /**
     * @return array<int, string>
     */
    private static function discordRoles(User $record): array
    {
        /** @var ExternalIdentity|null $discordIdentity */
        $discordIdentity = $record->providers->firstWhere('provider', IdentityProvider::Discord);

        if ($discordIdentity === null) {
            return [];
        }

        $member = DiscordMember::query()
            ->where('external_identity_id', $discordIdentity->id)
            ->with('roles')
            ->first();

        return $member?->roles
            ->map(static fn (DiscordRole $role): string => $role->name)
            ->all() ?? [];
    }
}
