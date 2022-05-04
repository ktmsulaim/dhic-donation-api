<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    public function subscribe(Request $request)
    {
        Validator::make($request->all(), [
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
        ])->validate();

        $student = Student::findOrFail($request->student_id);

        if(!$student->subscription) {
            $student->subscription()->create([
                'amount' => $request->amount,
                'interval' => $request->interval,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);
        }


        return response()->json(['data' => $student->subscription, 'success' => true]);
    }

    public function makeHistory(Subscription $subscription) {
        try {
            $subscription->createHistory();
    
            return response()->json(['data' => 'History created successfully', 'success' => true]);
        } catch (\Throwable $th) {
            return response()->json(['data' => $th->getMessage(), 'success' => false]);
        }
    }
}
