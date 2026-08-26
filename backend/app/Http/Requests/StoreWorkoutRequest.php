<?php

namespace App\Http\Requests;

use App\Enums\WorkoutStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Nessun campo nel body (started_at/status li imposta il controller): questo
 * Form Request esiste solo per validare la regola "un utente ha al massimo
 * un workout attivo", nello stesso posto in cui viene validato tutto il
 * resto, invece che inline nel controller.
 */
class StoreWorkoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $hasActiveWorkout = $this->user()->workouts()
                ->where('status', WorkoutStatus::Active)
                ->exists();

            if ($hasActiveWorkout) {
                $validator->errors()->add('workout', 'Hai già un allenamento attivo.');
            }
        });
    }
}
