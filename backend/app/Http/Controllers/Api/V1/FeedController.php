<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\WorkoutStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\FeedWorkoutResource;
use App\Models\Workout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class FeedController extends Controller
{
    /**
     * L'app gira in UTC (config('app.timezone')), ma "che giorno e'" un
     * allenamento deve rispecchiare il calendario dell'utente (Italia), non
     * quello UTC: un allenamento all'1 di notte locale e' salvato come le
     * 23:00 UTC del giorno prima, quindi raggrupparlo con DATE(started_at)
     * grezzo lo metterebbe nel giorno sbagliato. Percio' i confini
     * mese/giorno sono calcolati in questo fuso e poi convertiti in UTC solo
     * per interrogare il DB.
     */
    private const DISPLAY_TIMEZONE = 'Europe/Rome';

    /**
     * Nessuna tabella "feed" dedicata: e' derivato al volo dai workout,
     * coerente con AGENTS.md ("il feed usa richieste HTTP normali", nessuno
     * storage/broadcasting aggiuntivo). Il feed ora e' un calendario mensile
     * (questo endpoint, conteggio per giorno) + un dettaglio giornaliero
     * (vedi day()), non piu' una lista piatta: a differenza della vecchia
     * index(), qui includiamo anche i workout dell'utente stesso, non solo
     * degli amici, perche' il calendario/Gantt deve mostrare "tu e i tuoi
     * amici" nello stesso giorno.
     */
    public function summary(Request $request): JsonResponse
    {
        $month = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ])['month'] ?? now(self::DISPLAY_TIMEZONE)->format('Y-m');

        $start = Carbon::createFromFormat('Y-m', $month, self::DISPLAY_TIMEZONE)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $userIds = $request->user()->acceptedFriendIds()->push($request->user()->id);

        $counts = Workout::query()
            ->whereIn('user_id', $userIds)
            ->whereIn('status', [WorkoutStatus::Active, WorkoutStatus::Completed])
            ->whereBetween('started_at', [$start->copy()->utc(), $end->copy()->utc()])
            ->pluck('started_at')
            ->map(fn ($startedAt) => Carbon::parse($startedAt)->timezone(self::DISPLAY_TIMEZONE)->toDateString())
            ->countBy()
            ->sortKeys();

        return response()->json([
            'data' => $counts->map(fn ($count, $date) => ['date' => $date, 'count' => $count])->values(),
        ]);
    }

    public function day(Request $request): AnonymousResourceCollection
    {
        $date = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ])['date'];

        $dayStart = Carbon::createFromFormat('Y-m-d', $date, self::DISPLAY_TIMEZONE)->startOfDay();
        $dayEnd = $dayStart->copy()->endOfDay();

        $userIds = $request->user()->acceptedFriendIds()->push($request->user()->id);

        $workouts = Workout::query()
            ->whereIn('user_id', $userIds)
            ->whereIn('status', [WorkoutStatus::Active, WorkoutStatus::Completed])
            ->whereBetween('started_at', [$dayStart->copy()->utc(), $dayEnd->copy()->utc()])
            ->with('user')
            ->withCount('workoutExercises')
            ->orderBy('started_at')
            ->get();

        return FeedWorkoutResource::collection($workouts);
    }
}
