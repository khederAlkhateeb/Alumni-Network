<?php

namespace App\Enums;

enum enConnectionStatus: string
{
    case PENDING = "pending";
    case ACCEPTED = "accepted";
    case REJECTED = "rejected";
    case BLOCKED = "blocked";

    public static function toArray(): array
    {
        return array_map(fn(enConnectionStatus $status) => $status->value, self::cases());
    }
}


