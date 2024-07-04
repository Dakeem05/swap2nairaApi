<?php

namespace App\Models;

use App\Traits\CreateUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Request extends Model
{
    use HasFactory, CreateUuid, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'images' => 'object',
        'ecodes' => 'object',
    ];
}
