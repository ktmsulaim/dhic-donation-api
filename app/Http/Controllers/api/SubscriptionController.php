<?php

namespace App\Http\Controllers\api;

use App\Helpers\MoneyHelper;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Subscription;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpParser\Node\Stmt\TryCatch;

class SubscriptionController extends Controller
{
    public function subscribe(Request $request)
    {
        Validator::make(
            $request->all(),
            [
                'student_id' => 'required',
                'amount' => 'required',
                'interval' => 'required',
                'start_date' => 'required',
                'end_date' => 'nullable',
            ],
            [
                'student_id.required' => 'Student id is required',
                'amount.required' => 'Amount is required',
                'interval.required' => 'Interval is required',
                'start_date.required' => 'Start date is required',
            ]
        )->validate();

        $student = Student::findOrFail($request->student_id);

        if (!$student->subscription) {
            $student->subscription()->create([
                'amount' => $request->amount,
                'interval' => $request->interval,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);
        }

        $student->load('subscription');
        $student->subscription->createHistory();

        return response()->json(['data' => $student->subscription, 'success' => true]);
    }

    public function makeHistory(Subscription $subscription)
    {
        try {
            $subscription->createHistory();

            return response()->json(['data' => 'History created successfully', 'success' => true]);
        } catch (\Throwable $th) {
            return response()->json(['data' => $th->getMessage(), 'success' => false]);
        }
    }

    public function update(Request $request, Subscription $subscription)
    {
        Validator::make(
            $request->all(),
            [
                'amount' => 'required',
                'interval' => 'required',
                'start_date' => 'required',
                'end_date' => 'nullable',
            ],
            [
                'amount.required' => 'Amount is required',
                'interval.required' => 'Interval is required',
                'start_date.required' => 'Start date is required',
            ]
        )->validate();

        $start_date = null;
        // update history
        if($subscription->amount != $request->amount) {
            $start_date = Carbon::now();
        }

        if($subscription->interval != $request->interval || $subscription->start_date != $request->start_date || $subscription->end_date != $request->end_date) {
            $subscription->history()->delete();
        }

        $subscription->update([
            'amount' => $request->amount,
            'interval' => $request->interval,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        $subscription->fresh()->createHistory($start_date);

        return response()->json(['data' => $subscription, 'success' => true]);
    }

    public function updateHistory(Request $request, Subscription $subscription)
    {
        try {
            Validator::make($request->all(), [
                'payments' => 'required',
            ])->validate();
    
            $payments = $request->payments;
    
            if(!$payments || count($payments) == 0) {
                throw new \Exception('Payments are required', 400);
            }

            foreach($payments as $payment) {
                $history = $subscription->history()->where('id', $payment['id'])->first();

                if(!$history) {
                    throw new \Exception('Subscription payment history not found', 404);
                }

                if($payment['amount'] > $subscription->amount || $history['amount_paid'] + $payment['amount'] > $subscription->amount) {
                    throw new \Exception('Payment amount should not be greater than sponsored', 400);
                }

                
                $amount_due =  ($subscription->amount - $history['amount_paid']) - $payment['amount'];
                $partially_paid = $amount_due > 0 ? true : false;
                
                if($payment['amount'] < 0 && $amount_due > $subscription->amount) {
                    throw new \Exception('Due should not be greater than sponsored', 400);
                }

                if($history['amount_paid'] + $payment['amount'] == $subscription->amount) {
                    $partially_paid = false;
                }

                $history->update([
                    'amount_paid' => $history->amount_paid + $payment['amount'],
                    'amount_due' => $amount_due,
                    'partially_paid' => $partially_paid,
                ]);
            }

            return response()->json(['data' => 'Subscription payment history updated successfully', 'success' => true], 200);
        } catch (\Throwable $th) {
            return response()->json(['data' => $th->getMessage(), 'success' => false], 400);
        }
    }

    public function historyByYear(Subscription $subscription, $year)
    {
        $history = $subscription->history()->where('year', $year)->get();

        return response()->json(['data' => $history, 'success' => true]);
    }

    public function historyBetweenDate(Request $request, Subscription $subscription)
    {
        try {
            Validator::make($request->only('start_date', 'end_date'), [
                'start_date' => 'required',
                'end_date' => 'required',    
            ])->validate();

            $start_date = Carbon::parse($request->start_date);
            $end_date = Carbon::parse($request->end_date);
            $period = CarbonPeriod::create($start_date, '1 month', $end_date);

            $history = Collection::make();

            foreach ($period as $date) {
                $history->push($subscription->history()->where('year', $date->year)->where('month', $date->month)->first());
            }
            $count = $history->count();
            return response()->json(['data' => [
                'history' => $history,
                'total' => $history->where('amount_due', '>', 0)->count(),
                'total_amount' => MoneyHelper::format($count * $subscription->amount), 
                'total_paid' => MoneyHelper::format($history->sum('amount_paid')),
                'total_due' => MoneyHelper::format($history->sum('amount_due')),
            ], 'success' => true]);
            
        } catch (\Throwable $th) {
            return response()->json(['data' => $th->getMessage(), 'success' => false], 400);
        }
    }
}
