<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Saving extends BaseModel
{
    protected $fillable = [
        'user_id',
        'name',
        'current_amount',
        'account_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
