<?php

namespace App\Http\Controllers\api;

use App\Helpers\MoneyHelper;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\SubscriptionHistory;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function summary(Request $request)
    {
        $data = [
            'students' => Student::active()->count(),
            'amount_of_the_month' => [
                'sponsored' => [
                    'amount' => SubscriptionHistory::amountOfTheMonth(),
                    'formatted' => MoneyHelper::format(SubscriptionHistory::amountOfTheMonth()),
                ],
                'amount_due' => [
                    'amount' => SubscriptionHistory::amountOfTheMonth('due'),
                    'formatted' => MoneyHelper::format(SubscriptionHistory::amountOfTheMonth('due')),
                    'percentage' => SubscriptionHistory::amountOfTheMonth('due') / SubscriptionHistory::amountOfTheMonth() * 100,
                ],
                'amount_paid' => [
                    'amount' => SubscriptionHistory::amountOfTheMonth('paid'),
                    'formatted' => MoneyHelper::format(SubscriptionHistory::amountOfTheMonth('paid')),
                    'percentage' => SubscriptionHistory::amountOfTheMonth('paid') / SubscriptionHistory::amountOfTheMonth() * 100,
                ]
            ],
            'amount_last_six_months' => [
                'amount_due' => [
                    'amount' => SubscriptionHistory::amountOfLastSixMonths('due'),
                ],
                'amount_paid' => [
                    'amount' => SubscriptionHistory::amountOfLastSixMonths('paid'),
                ],
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }
}
