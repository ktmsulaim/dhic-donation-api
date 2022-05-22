<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    public static function amountOfTheMonth($type = 'total')
    {
        $column = 'amount_due';

        if($type == 'total') {
            $column = DB::raw('amount_due + amount_paid');
        } elseif($type == 'due') {
            $column = 'amount_due';
        } elseif($type == 'paid') {
            $column = 'amount_paid';
        }

        return self::where([
            'month' => date('n'),
            'year' => date('Y'),
        ])->sum($column);
    }

    public static function amountOfLastSixMonths($type = 'due')
    {
        $column = 'amount_due';

       if($type == 'paid') {
            $column = 'amount_paid';
        }

        $period = now()->subMonths(6)->monthsUntil(now());
        $now = Carbon::now();
        $data = [];

        foreach ($period as $date) {
            array_push($data, [
                'label' => $now->year != $date->year ? $date->format('M \'y') : $date->format('M'),
                'amount' => self::where([
                    'month' => $date->month,
                    'year' => $date->year,
                ])->sum($column),
            ]);
        }
        
        return $data;
    }
}
