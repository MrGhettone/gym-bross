<?php

namespace App\Policies;

use App\Enums\FriendshipStatus;
use App\Models\Friendship;
use App\Models\User;

class FriendshipPolicy
{
    /**
     * L'utente vede la relazione solo se ne fa parte.
     */
    public function view(User $user, Friendship $friendship): bool
    {
        return $user->id === $friendship->requester_id || $user->id === $friendship->addressee_id;
    }

    /**
     * Chiunque puo' tentare di inviare una richiesta: i vincoli (non a se
     * stessi, nessuna relazione duplicata/opposta gia' esistente) sono
     * validati in StoreFriendshipRequest, non qui.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Solo chi ha ricevuto la richiesta puo' accettarla, e solo se pending.
     */
    public function accept(User $user, Friendship $friendship): bool
    {
        return $user->id === $friendship->addressee_id
            && $friendship->status === FriendshipStatus::Pending;
    }

    /**
     * Solo chi ha ricevuto la richiesta puo' rifiutarla, e solo se pending.
     */
    public function reject(User $user, Friendship $friendship): bool
    {
        return $user->id === $friendship->addressee_id
            && $friendship->status === FriendshipStatus::Pending;
    }

    /**
     * Solo chi ha inviato la richiesta puo' annullarla, e solo se pending.
     */
    public function cancel(User $user, Friendship $friendship): bool
    {
        return $user->id === $friendship->requester_id
            && $friendship->status === FriendshipStatus::Pending;
    }

    /**
     * Entrambe le parti possono rimuovere un'amicizia accettata.
     */
    public function remove(User $user, Friendship $friendship): bool
    {
        return $this->isParticipant($user, $friendship)
            && $friendship->status === FriendshipStatus::Accepted;
    }

    /**
     * Entrambe le parti possono bloccare, in qualsiasi stato (tranne se gia' bloccata).
     */
    public function block(User $user, Friendship $friendship): bool
    {
        return $this->isParticipant($user, $friendship)
            && $friendship->status !== FriendshipStatus::Blocked;
    }

    private function isParticipant(User $user, Friendship $friendship): bool
    {
        return $user->id === $friendship->requester_id || $user->id === $friendship->addressee_id;
    }
}
