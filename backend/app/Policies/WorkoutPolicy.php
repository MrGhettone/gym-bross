<?php

namespace App\Policies;

use App\Enums\FriendshipStatus;
use App\Enums\WorkoutStatus;
use App\Models\Friendship;
use App\Models\User;
use App\Models\Workout;

class WorkoutPolicy
{
    /**
     * Il proprietario vede sempre il proprio workout; un amico accettato lo
     * vede in sola lettura (e' quello che alimenta il feed, Fase 5) — nessun
     * altro utente puo' vederlo.
     */
    public function view(User $user, Workout $workout): bool
    {
        if ($user->id === $workout->user_id) {
            return true;
        }

        return Friendship::query()
            ->where('status', FriendshipStatus::Accepted)
            ->between($user->id, $workout->user_id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, Workout $workout): bool
    {
        return $user->id === $workout->user_id;
    }

    public function finish(User $user, Workout $workout): bool
    {
        return $user->id === $workout->user_id && $workout->status === WorkoutStatus::Active;
    }

    public function cancel(User $user, Workout $workout): bool
    {
        return $user->id === $workout->user_id && $workout->status === WorkoutStatus::Active;
    }

    /**
     * Usata anche per gestire esercizi/serie del workout (WorkoutExercise,
     * WorkoutSet): sono sotto-risorse senza una propria policy dedicata,
     * "posso modificare questo workout" e' la domanda giusta anche per loro.
     * Solo un workout attivo e' modificabile: completato/annullato = storico
     * di sola lettura.
     */
    public function manageExercises(User $user, Workout $workout): bool
    {
        return $user->id === $workout->user_id && $workout->status === WorkoutStatus::Active;
    }
}
