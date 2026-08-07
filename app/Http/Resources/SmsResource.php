<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SmsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_id' => $this->device_id,
            'device_name' => $this->whenLoaded('device', fn() => $this->device->name),
            'phone' => $this->phone,
            'message' => $this->message,
            'received_at' => $this->received_at ? $this->received_at->toDateTimeString() : null,
            'received_at_human' => $this->received_at ? $this->received_at->diffForHumans() : null,
            'processed' => (bool) $this->processed,
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
        ];
    }
}
