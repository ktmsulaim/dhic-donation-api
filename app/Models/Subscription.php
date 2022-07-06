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

    public $intervals = [
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

    public function createHistory($start_date = null)
    {
        if(!$start_date) {
            $start_date = Carbon::parse($this->start_date);
        } elseif (!$start_date instanceof Carbon) {
            $start_date = Carbon::parse($start_date);
        }

        $end_date = $this->end_date ? Carbon::parse($this->end_date) : Carbon::now();

        $interval = $this->interval;
        $periods = CarbonPeriod::create($start_date, $this->intervals[$interval], $end_date);

        foreach ($periods as $period) {
            $history = $this->history()->where('month', $period->month)->where('year', $period->year)->first();
            
            if (!$history) {
                $history = $this->history()->create([
                    'month' => $period->month,
                    'year' => $period->year,
                    'amount_due' => $this->amount,
                ]);
            } else {
                $history->update([
                    'amount_due' => $history->amount_paid > 0 ? $this->amount - $history->amount_paid : $this->amount,
                    'partially_paid' => $history->amount_paid > 0 ? 1 : 0
                ]);
            }

            // delete duplicates
            $duplicates = $this->history()->where('month', $period->month)->where('year', $period->year)->where('id', '!=', $history->id)->get();

            if($duplicates && $duplicates->count()) {
                $duplicates->each(function($duplicate) {
                    $duplicate->delete();
                });
            }
        }

        // clean up unused history
        $unusedHistory = $this->history()->where(function($query) use ($start_date){
            $query->where('month', '<', $start_date->month)
                ->where('year', '<', $start_date->year);
        })->get();

        $unusedAfterHistory = $this->history()->where(function($query) use ($end_date) {
            $query->where('month', '>', $end_date->month)
                ->where('year', '>', $end_date->year);
        })->get();

        $unusedHistory->concat($unusedAfterHistory);

        if($unusedHistory && $unusedHistory->count()) {
            $unusedHistory->each(function($history) {
                $history->delete();
            });
        }

    }
}
