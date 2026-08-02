<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Connection Rejection Cooldown
    |--------------------------------------------------------------------------
    |
    | The number of days a receiver's rejection stays active. A requester
    | cannot re-send a connection request to the same receiver until this
    | cooldown period has elapsed.
    |
    */

    'rejection_cooldown_days' => env('CONNECTION_REJECTION_COOLDOWN_DAYS', 30),
];
