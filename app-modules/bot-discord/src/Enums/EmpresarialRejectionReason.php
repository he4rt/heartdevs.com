<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Enums;

/**
 * Why a `/sala-empresarial` privatization request was rejected by the decision seam.
 */
enum EmpresarialRejectionReason: string
{
    case NotInTrackedRoom = 'not-in-tracked-room';
    case MissingPartnerRole = 'missing-partner-role';
    case UnknownCompany = 'unknown-company';

    /**
     * Human-readable, ephemeral-friendly message shown to the caller.
     */
    public function message(): string
    {
        return match ($this) {
            self::NotInTrackedRoom => '❌ Você precisa estar dentro de uma sala criada com /sala para usar este comando!',
            self::MissingPartnerRole => '❌ Você não faz parte da empresa selecionada e não pode torná-la privada.',
            self::UnknownCompany => '❌ Empresa parceira não encontrada.',
        };
    }
}
