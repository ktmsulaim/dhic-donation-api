<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\SubscriptionHistory;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function summary(Request $request)
    {
        $data = [
            'total_students' => Student::count(),
            'students' => Student::active()->count(),
            'sponsored_amount_of_the_month' => SubscriptionHistory::amountOfTheMonth(),
            'due_amount_of_the_month' => SubscriptionHistory::amountOfTheMonth('due'),
            'paid_amount_of_the_month' => SubscriptionHistory::amountOfTheMonth('paid'),
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }
}
