<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'amount', 'interval', 'start_date', 'end_date',
    ];

    private $intervals = [
        0 => '1 month',
        1 => '3 months',
        2 => '6 months',
        3 => '1 year',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function history()
    {
        return $this->hasMany(SubscriptionHistory::class);
    }

    public function createHistory()
    {
        $start_date = Carbon::parse($this->start_date);
        $end_date = $this->end_date ? Carbon::parse($this->end_date) : Carbon::now();

        $interval = $this->interval;
        $periods = CarbonPeriod::create($start_date, $this->intervals[$interval], $end_date);

        foreach ($periods as $period) {
            if (!$this->history()->where('month', $period->month)->where('year', $period->year)->exists()) {
                $this->history()->create([
                    'month' => $period->month,
                    'year' => $period->year,
                    'amount_due' => $this->amount,
                ]);
            }
        }
    }
}
