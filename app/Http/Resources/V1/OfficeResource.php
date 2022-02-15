<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class OfficeResource extends JsonResource
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
            'user'          => UserResource::make($this->user),
            'images'        => ImageResource::collection($this->whenLoaded('images')),
            'tags'          => TagResource::collection($this->whenLoaded('tags')),
            'reservations'  => ReservationResource::collection($this->whenLoaded('reservations')),
            $this->merge(
                Arr::except(parent::toArray($request), [
                    'user_id', 'created_at', 'updated_at', 'deleted_at',
                ])
            )
        ];

    }
}
