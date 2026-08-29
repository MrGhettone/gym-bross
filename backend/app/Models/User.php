<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\FriendshipStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasPushSubscriptions, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = ['username', 'email', 'password'];

    /**
     * @var list<string>
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function sentFriendRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'requester_id');
    }

    public function receivedFriendRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'addressee_id');
    }

    public function workouts(): HasMany
    {
        return $this->hasMany(Workout::class);
    }

    /**
     * Id degli utenti con cui ho un'amicizia accettata, indipendentemente
     * da chi ha inviato la richiesta. Usato da FeedController e dalle
     * notifiche di attività (Fase 7).
     *
     * @return Collection<int, int>
     */
    public function acceptedFriendIds(): Collection
    {
        return Friendship::query()
            ->where('status', FriendshipStatus::Accepted)
            ->involvingUser($this->id)
            ->get()
            ->map(fn (Friendship $friendship) => $friendship->requester_id === $this->id
                ? $friendship->addressee_id
                : $friendship->requester_id);
    }
}
