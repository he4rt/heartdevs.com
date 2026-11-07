<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido\Stream;

enum FrameKind
{
    case Open;
    case Ping;
    case Connected;
    case ConnectError;
    case Disconnected;
    case Event;
    case Ack;
    case Unknown;
}
