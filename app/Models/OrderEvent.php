<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderEvent extends Model
{
    protected $fillable = ['status', 'description'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
