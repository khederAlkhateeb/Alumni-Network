<?php

namespace App\Models;


use App\Builders\ConnectionQueryBuilder;
use App\Enums\enConnectionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


#[Fillable([
  'requester_id',
  'receiver_id',
  'status',
  'accepted_at',
  'rejected_at',
])]
class Connection extends Model
{
  use HasFactory;
  
  
    /**
     * Cast the status to accept the enum directly like : enConnectionType::rejected without  ->value to get the value
     *
     * @var array
     */
    protected $casts = [
        'status' => enConnectionStatus::class,
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function newEloquentBuilder($query): ConnectionQueryBuilder
    {
        return new ConnectionQueryBuilder($query);
    }

    // Relationships

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
