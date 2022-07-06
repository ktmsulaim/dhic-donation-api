<?php

namespace App\Models;

use App\Helpers\MoneyHelper;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'photo', 'place', 'dob', 'adno', 'class', 'active'
    ];

    protected $with = ['subscription'];

    public function hasPhoto() {
        if($this->photo) {
            return Storage::exists('students/photos/thumbnail/' . $this->photo);
        }

        return false;
    }

    public function getPhotoUrl($size = 'thumbnail') {
        if($this->hasPhoto()) {
            return Storage::url('students/photos/' . $size . '/' . $this->photo);
        }
    }

    public function subscription() {
        return $this->hasOne(Subscription::class);
    }

    public function history()
    {
        return $this->hasManyThrough(SubscriptionHistory::class, Subscription::class);
    }


    public function scopeActive($query)
    {
        $query->where('active', 1);
    }

    public function getHumanReadableSubscription()
    {
        if($this->subscription()->exists()) {
            $amount = $this->subscription->amount;
            $subscription = MoneyHelper::format($amount);
            $interval = $this->subscription->interval;

            if(!$amount > 0) {
                $subscription = 'No donation';
            }

            switch ($interval) {
                case 0:
                    $subscription .= " / Month";
                    break;
                case 1:
                    $subscription .= " / 3 Months";
                    break;
                case 2:
                    $subscription .= " / 6 Months";
                    break;
                case 3:
                    $subscription .= " / Year";
                    break;
                default:
                    $subscription .= " / " . $interval . " Months";
                    break;
            }

            return $subscription;
        }

        return "Not sponsored";
    }

    public function getDueTill($month = null, $year = null)
    {
        if(!$this->subscription()->exists()) return 0;

        if(!$month) {
            $month = date('m');
        }

        if(!$year) {
            $year = date('Y');
        }

        $due = 0;

        $start_date = Carbon::parse($this->subscription->start_date);
        $end_date = Carbon::create($year, $month, 1)->endOfMonth();
        $interval = $this->subscription->intervals[$this->subscription->interval];
        $periods = CarbonPeriod::create($start_date, $interval, $end_date);

        foreach ($periods as $period) {
            $history = $this->history()->where('month', $period->month)->where('year', $period->year)->first();

            if($history) {
                $due += $history->amount_due;
            }
        }

        return $due;
    }
}
