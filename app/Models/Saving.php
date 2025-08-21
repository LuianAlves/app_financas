<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Saving extends Model
{
    use HasUuids;

    protected $table = 'savings';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'account_id',
        'name',
        'current_amount',
        'interest_rate',
        'rate_period',
        'start_date',
        'notes',
    ];

    protected $casts = [
        'current_amount' => 'float',
        'interest_rate'  => 'float',
        'start_date'     => 'date',

    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}


