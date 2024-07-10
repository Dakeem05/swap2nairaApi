<?php

namespace App\Models;

use App\Traits\CreateUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use HasFactory, SoftDeletes, CreateUuid;
    protected $guarded = [];

    public function notification (): BelongsTo
    {
        return $this->belongsTo(Notification::class, 'user_id', 'id');
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->diffForHumans();
    }

    public static function Notify ($id, $message) {
		$notify = Notification::create([
            'user_id' => $id,
            'message' => $message
        ]);
        return $notify; 
	}
}
