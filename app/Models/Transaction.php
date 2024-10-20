<?php

namespace App\Models;

use App\Traits\CreateUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, CreateUuid, SoftDeletes;

    protected $guarded = [];

    protected $hidden = [
        'id',
        'deleted_at',
        'updated_at',
        'user_id',
        'request_id'
    ];

    protected $casts = [
        'sent_mail' => 'boolean'
    ];

    public function user():BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id'); 
    }

    public function wallet():BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id', 'id');
    }
}
