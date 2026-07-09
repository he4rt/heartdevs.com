<?php

declare(strict_types=1);

namespace He4rt\Squads\Enums;

enum SquadRole: string
{
    case Captain = 'captain';
    case SubCaptain = 'sub_captain';
    case Member = 'member';
    case ExMember = 'ex_member';
}
