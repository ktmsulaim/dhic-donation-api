<?php

namespace App\Http\Controllers\api;

use App\Exports\DueByClassExport;
use App\Helpers\MoneyHelper;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use PhpParser\Node\Stmt\TryCatch;

class ReportController extends Controller
{
    public function summary(Request $request)
    {
        $amount_of_the_month = SubscriptionHistory::amountOfTheMonth();
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
                    'percentage' => $amount_of_the_month ? SubscriptionHistory::amountOfTheMonth('due') / $amount_of_the_month  * 100 : 0,
                ],
                'amount_paid' => [
                    'amount' => SubscriptionHistory::amountOfTheMonth('paid'),
                    'formatted' => MoneyHelper::format(SubscriptionHistory::amountOfTheMonth('paid')),
                    'percentage' => $amount_of_the_month ? SubscriptionHistory::amountOfTheMonth('paid') / $amount_of_the_month * 100 : 0,
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

    public function classWise(Request $request)
    {
        try {
            Validator::make($request->all(), [
                'year' => 'required',
                'month' => 'required',
                'class' => 'required',
            ])->validate();

            $class = $request->get('class');
            $status = $request->get('status') ?? 'due'; // paid : due
            $month = $request->get('month');
            $year = $request->get('year');

            $students = Student::active()->where('class', $class)->whereHas('history', function($q) use($status, $month, $year) {
                if($year && $month) {
                    $q->where('year', $year)
                        ->where('month', $month);
                } else if ($year) {
                    $q->where('year', $year);
                } else if ($month) {
                    $q->where('year', date('Y'))->where('month', $month);
                }

                $q->groupBy('subscription_history.id');

                if($status === 'paid') {
                    $q->havingRaw('SUM(amount_paid) > 0');
                } else {
                    $q->havingRaw('SUM(amount_due) > 0');
                }
            })->get();

            if($year && $month) {
                foreach ($students as $key => $student) {
                    $student->history = $student->history()->where('year', $year)->where('month', $month)->first();
                }
            }

            return response()->json([
                'success' => true,
                'data' => $students,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function monthly(Request $request)
    {
        try {
            Validator::make($request->all(), [
                'year' => 'required',
                'month' => 'required',
            ])->validate();

            $month = $request->get('month');
            $year = $request->get('year');

            $data = [];

            for ($i=1; $i < 11; $i++) { 
                $paid = SubscriptionHistory::amountByClass($i, $year, $month, 'paid');
                $due = SubscriptionHistory::amountByClass($i, $year, $month, 'due');

                array_push($data, [
                    'Class ' . $i => [
                        'sponsored' => MoneyHelper::format($due + $paid),
                        'amount_due' => MoneyHelper::format($due),
                        'amount_paid' => MoneyHelper::format($paid),
                    ]
                ]);
            }

            $total_paid = SubscriptionHistory::where('year', $year)->where('month', $month)->sum('amount_paid');
            $total_due = SubscriptionHistory::where('year', $year)->where('month', $month)->sum('amount_due');

            array_push($data, [
                'Total' => [
                    'sponsored' => MoneyHelper::format($total_paid + $total_due),
                    'amount_due' => MoneyHelper::format($total_due),
                    'amount_paid' => MoneyHelper::format($total_paid)
                ]
            ]);

            return response()->json([
                'success' => true,
                'data' => $data,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function due(Request $request)
    {
        try {
            Validator::make($request->all(), [
                'class' => 'required',
                'year' => 'required',
                'month' => 'required'
            ])->validate();

            $class = $request->get('class');
            $year = $request->get('year');
            $month = $request->get('month');

            $data = [];

            $students = Student::active()->where('class', $class)->get();

            foreach($students as $student) {
                array_push($data, [
                    'name' => $student->name,
                    'adno' => $student->adno, 
                    'donation' => $student->getHumanReadableSubscription(),
                    'amount_due' => $student->getDueTill($month, $year),
                    'amount_due_formatted' => MoneyHelper::format($student->getDueTill($month, $year)),
                ]);
            }

            array_push($data, [
                'name' => 'Total',
                'adno' => '',
                'donation' => '',
                'amount_due' => Collect($data)->sum('amount_due'),
                'amount_due_formatted' => MoneyHelper::format(Collect($data)->sum('amount_due'))
            ]);

            return response()->json([
                'success' => true,
                'data' => $data,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    
    public function exportDue(Request $request)
    {
        try {
            Validator::make($request->all(), [
                'class' => 'required',
                'year' => 'required',
                'month' => 'required'
            ])->validate();

            $class = $request->get('class');
            $year = $request->get('year');
            $month = $request->get('month');

            $data = [];

            $students = Student::active()->where('class', $class)->get();

            foreach($students as $student) {
                array_push($data, [
                    'name' => $student->name,
                    'adno' => $student->adno, 
                    'donation' => $student->getHumanReadableSubscription(),
                    'amount_due' => $student->getDueTill($month, $year),
                    'amount_due_formatted' => MoneyHelper::format($student->getDueTill($month, $year)),
                ]);
            }

            array_push($data, [
                'name' => 'Total',
                'adno' => '',
                'donation' => '',
                'amount_due' => Collect($data)->sum('amount_due'),
                'amount_due_formatted' => MoneyHelper::format(Collect($data)->sum('amount_due'))
            ]);

            $data = collect($data)->map(function($item) {
                return [
                    'Name' => $item['name'],
                    'Admission No' => $item['adno'],
                    'Donation' => $item['donation'],
                    'Amount Due' => $item['amount_due_formatted'],
                ];
            });

            return Excel::download(new DueByClassExport($data), 'Due_Class_'.$class.'_'.date('d_m_Y').'.xlsx');
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
