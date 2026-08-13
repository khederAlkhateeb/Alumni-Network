<?php

namespace App\Enums;

enum EventType: string
{
    case Campus = 'campus';
    case Online = 'online';
    case Hybrid = 'hybrid';
}
