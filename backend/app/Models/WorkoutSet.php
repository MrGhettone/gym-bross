<?php

namespace App\Models;

use Database\Factories\WorkoutSetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutSet extends Model
{
    /** @use HasFactory<WorkoutSetFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['workout_exercise_id', 'set_number', 'weight', 'repetitions', 'duration', 'distance'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'distance' => 'decimal:2',
        ];
    }

    public function workoutExercise(): BelongsTo
    {
        return $this->belongsTo(WorkoutExercise::class);
    }
}
