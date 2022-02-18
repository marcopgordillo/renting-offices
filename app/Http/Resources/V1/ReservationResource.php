<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class ReservationResource extends JsonResource
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
            'user'          => UserResource::make($this->whenLoaded('user')),
            'office'        => OfficeResource::make($this->whenLoaded('office')),
            $this->merge(
                Arr::except(parent::toArray($request), [
                    'user_id', 'office_id', 'created_at', 'updated_at',
                ])
            )
        ];
    }
}
