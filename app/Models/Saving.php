<?php

namespace App\Models;

use App\Models\Auth\User;
use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Saving extends BaseModel
{
    use BelongsToUser;
    protected $fillable = [
        'user_id',
        'name',
        'current_amount',
        'color_card',
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
