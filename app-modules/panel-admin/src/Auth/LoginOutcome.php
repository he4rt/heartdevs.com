<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Auth;

enum LoginOutcome
{
    case SignedIn;
    case AwaitingSecondFactor;
}
