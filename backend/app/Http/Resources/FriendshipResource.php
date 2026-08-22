<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FriendshipResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'direction' => $this->requester_id === $request->user()?->id ? 'outgoing' : 'incoming',
            'requester' => new PublicUserResource($this->whenLoaded('requester')),
            'addressee' => new PublicUserResource($this->whenLoaded('addressee')),
            'created_at' => $this->created_at,
        ];
    }
}
