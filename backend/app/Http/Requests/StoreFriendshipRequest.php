<?php

namespace App\Http\Requests;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreFriendshipRequest extends FormRequest
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
            'username' => ['required', 'string', 'exists:users,username'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $username = $this->string('username')->value();
            $addressee = User::where('username', $username)->first();

            if (! $addressee) {
                return;
            }

            if ($addressee->id === $this->user()->id) {
                $validator->errors()->add('username', 'Non puoi inviare una richiesta di amicizia a te stesso.');

                return;
            }

            $exists = Friendship::query()
                ->between($this->user()->id, $addressee->id)
                ->exists();

            if ($exists) {
                $validator->errors()->add('username', 'Esiste gia\' una relazione con questo utente.');
            }
        });
    }
}
