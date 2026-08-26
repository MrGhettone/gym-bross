<?php

namespace App\Policies;

use App\Enums\WorkoutStatus;
use App\Models\User;
use App\Models\Workout;

class WorkoutPolicy
{
    public function view(User $user, Workout $workout): bool
    {
        return $user->id === $workout->user_id;
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
