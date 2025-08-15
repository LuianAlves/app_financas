<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends BaseModel
{
    protected $fillable = [
        'transaction_id',
        'title',
        'amount',
        'payment_date',
        'reference_month',
        'reference_year'
    ];
}
