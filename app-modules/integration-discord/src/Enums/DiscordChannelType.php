<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum DiscordChannelType: int implements HasColor, HasDescription, HasIcon, HasLabel
{
    case GuildText = 0;
    case Dm = 1;
    case GuildVoice = 2;
    case GroupDm = 3;
    case GuildCategory = 4;
    case GuildAnnouncement = 5;
    case AnnouncementThread = 10;
    case PublicThread = 11;
    case PrivateThread = 12;
    case GuildStageVoice = 13;
    case GuildForum = 15;
    case GuildMedia = 16;

    public function getLabel(): string
    {
        return match ($this) {
            self::GuildText => 'Texto',
            self::Dm => 'DM',
            self::GuildVoice => 'Voz',
            self::GroupDm => 'DM em grupo',
            self::GuildCategory => 'Categoria',
            self::GuildAnnouncement => 'Anúncios',
            self::AnnouncementThread => 'Thread de anúncio',
            self::PublicThread => 'Thread pública',
            self::PrivateThread => 'Thread privada',
            self::GuildStageVoice => 'Palco',
            self::GuildForum => 'Fórum',
            self::GuildMedia => 'Mídia',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::GuildText => 'gray',
            self::Dm => 'info',
            self::GuildVoice => 'success',
            self::GroupDm => 'info',
            self::GuildCategory => 'gray',
            self::GuildAnnouncement => 'warning',
            self::AnnouncementThread => 'warning',
            self::PublicThread => 'primary',
            self::PrivateThread => 'primary',
            self::GuildStageVoice => 'danger',
            self::GuildForum => 'primary',
            self::GuildMedia => 'info',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::GuildText => 'Canal de texto padrão do servidor',
            self::Dm => 'Mensagem direta entre dois usuários',
            self::GuildVoice => 'Canal de voz do servidor',
            self::GroupDm => 'Mensagem direta em grupo entre vários usuários',
            self::GuildCategory => 'Categoria usada para agrupar outros canais',
            self::GuildAnnouncement => 'Canal de anúncios que pode ser seguido por outros servidores',
            self::AnnouncementThread => 'Thread criada a partir de um canal de anúncios',
            self::PublicThread => 'Thread pública visível a todos os membros do canal',
            self::PrivateThread => 'Thread privada visível apenas a membros convidados',
            self::GuildStageVoice => 'Canal de palco para eventos de áudio ao vivo',
            self::GuildForum => 'Canal de fórum organizado em posts com threads',
            self::GuildMedia => 'Canal de mídia organizado em posts com anexos',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::GuildText => Heroicon::OutlinedHashtag,
            self::Dm => Heroicon::OutlinedEnvelope,
            self::GuildVoice => Heroicon::OutlinedSpeakerWave,
            self::GroupDm => Heroicon::OutlinedEnvelope,
            self::GuildCategory => Heroicon::OutlinedFolder,
            self::GuildAnnouncement => Heroicon::OutlinedMegaphone,
            self::AnnouncementThread => Heroicon::OutlinedChatBubbleBottomCenterText,
            self::PublicThread => Heroicon::OutlinedChatBubbleBottomCenterText,
            self::PrivateThread => Heroicon::OutlinedChatBubbleBottomCenterText,
            self::GuildStageVoice => Heroicon::OutlinedMicrophone,
            self::GuildForum => Heroicon::OutlinedChatBubbleLeftRight,
            self::GuildMedia => Heroicon::OutlinedPhoto,
        };
    }
}
