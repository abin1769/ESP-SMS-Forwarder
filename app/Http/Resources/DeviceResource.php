<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource
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
            'name' => $this->name,
            'token' => $this->token,
            'status' => $this->status,
            'is_online' => $this->is_online,
            'signal' => $this->signal,
            'operator' => $this->operator,
            'sim_status' => $this->sim_status ?? 'UNKNOWN',
            'reg_status' => $this->reg_status ?? 'UNKNOWN',
            'pending_command' => $this->pending_command,
            'command_response' => $this->command_response,
            'command_updated_at' => $this->command_updated_at ? $this->command_updated_at->toDateTimeString() : null,
            'last_seen' => $this->last_seen ? $this->last_seen->toDateTimeString() : null,
            'last_seen_human' => $this->last_seen ? $this->last_seen->diffForHumans() : 'Never',
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
        ];
    }
}
