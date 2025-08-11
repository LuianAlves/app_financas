<?php

namespace App\Models;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recurrent extends BaseModel
{
    protected $fillable = [
        'user_id',
        'transaction_id',
        'payment_day',
        'amount'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
