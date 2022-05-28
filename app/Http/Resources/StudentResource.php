<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
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
            'name' => $this->name,
            'place' => $this->place,
            'dob' => $this->dob,
            'adno' => $this->adno,
            'class' => $this->class,
            'status' => $this->active,
            'photo' => $this->when($this->hasPhoto(), [
                'thumbnail' => $this->getPhotoUrl('thumbnail'),
                'medium' => $this->getPhotoUrl('medium'), 
            ]),
            'created_at' => [
                'date' => $this->created_at->format('d-m-Y'),
                'time' => $this->created_at->format('h:i:s A'),
                'timestamp' => $this->created_at->timestamp,
            ],
            'updated_at' => [
                'date' => $this->updated_at->format('d-m-Y'),
                'time' => $this->updated_at->format('h:i:s A'),
                'timestamp' => $this->updated_at->timestamp,
            ],
            'subscription_summary' => $this->getHumanReadableSubscription(),
            'subscription' => $this->whenLoaded('subscription', new SubscriptionResource($this->subscription)),
        ];
    }
}
