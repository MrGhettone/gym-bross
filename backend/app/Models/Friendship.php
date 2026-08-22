<?php

namespace App\Models;

use App\Enums\FriendshipStatus;
use Database\Factories\FriendshipFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Friendship extends Model
{
    /** @use HasFactory<FriendshipFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['requester_id', 'addressee_id', 'status'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FriendshipStatus::class,
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function addressee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'addressee_id');
    }

    /**
     * Relazioni (in qualsiasi direzione/stato) che coinvolgono l'utente.
     */
    public function scopeInvolvingUser(Builder $query, int $userId): Builder
    {
        return $query->where('requester_id', $userId)->orWhere('addressee_id', $userId);
    }

    /**
     * Relazione tra due utenti specifici, indipendentemente dalla direzione.
     * Usata per impedire richieste duplicate/opposte.
     */
    public function scopeBetween(Builder $query, int $userIdA, int $userIdB): Builder
    {
        return $query->where(function (Builder $query) use ($userIdA, $userIdB) {
            $query->where(['requester_id' => $userIdA, 'addressee_id' => $userIdB])
                ->orWhere(['requester_id' => $userIdB, 'addressee_id' => $userIdA]);
        });
    }
}
