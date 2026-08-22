<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Profilo pubblico di un utente, visibile ad altri utenti (es. dentro una
 * FriendshipResource). A differenza di UserResource non espone l'email:
 * quella resta riservata alla risposta del proprio /me.
 */
class PublicUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'avatar' => $this->avatar,
        ];
    }
}
