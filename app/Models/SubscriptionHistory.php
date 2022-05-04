<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id', 'partially_paid', 'amount_paid', 'amount_due', 'month', 'year'
    ];

    protected $table = 'subscription_history';

    public function subscription() {
        return $this->belongsTo(Subscription::class);
    }
}
