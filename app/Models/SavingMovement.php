<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class SavingMovement extends BaseModel
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'saving_id',
        'transaction_id',
        'account_id',
        'direction',
        'amount',
        'date',
        'notes'
    ];
}
