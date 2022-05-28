<?php

namespace App\Http\Resources;

use App\Helpers\MoneyHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'amount' => [
                'total' => $this->amount,
                'paid' => $this->history->sum('amount_paid'),
                'paid_formatted' => MoneyHelper::format($this->history->sum('amount_paid')),
                'remaining' => $this->history->sum('amount_due'),
                'remaining_formatted' => MoneyHelper::format($this->history->sum('amount_due')),
                'formatted' => MoneyHelper::format($this->amount),
            ],
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'interval' => $this->interval,
            'years' => $this->history->groupBy('year')->map(function ($year) {
                return $year->first()->year;
            })->toArray(),
        ];
    }
}
