<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardTransaction extends BaseModel
{
    protected $fillable = [
        'invoice_id',
        'card_id',
        'description',
        'amount',
        'date',
        'installments',
        'current_installment',
        'category_id',
    ];

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class);
    }
}
