<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends BaseModel
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'card_id',
        'current_month',
        'paid',
    ];

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
