<?php

namespace App\Models;

use App\Traits\CreateUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Card extends Model
{
    use HasFactory, CreateUuid, SoftDeletes;
    
    protected $guarded = [];

    protected $casts = [
        'active' => 'boolean'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function requests ():HasMany
    {
        return $this->hasMany(Request::class, 'card_id', 'id');
    }
}
