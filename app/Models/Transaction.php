<?php

namespace App\Models;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends BaseModel
{
    protected $fillable = [
        'user_id',
        'card_id',
        'transaction_category_id',
        'title',
        'description',
        'amount',
        'date',
        'type',
        'type_card',
        'recurrence_type',
        'custom_occurrences'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactionCategory(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }
}
