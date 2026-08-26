<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreWorkoutSetRequest extends FormRequest
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
        return [
            'weight' => ['nullable', 'numeric', 'min:0'],
            'repetitions' => ['nullable', 'integer', 'min:0'],
            'duration' => ['nullable', 'integer', 'min:0'],
            'distance' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $hasAnyMetric = collect(['weight', 'repetitions', 'duration', 'distance'])
                ->contains(fn (string $field) => $this->filled($field));

            if (! $hasAnyMetric) {
                $validator->errors()->add('weight', 'Indica almeno una misura (peso, ripetizioni, durata o distanza).');
            }
        });
    }
}
