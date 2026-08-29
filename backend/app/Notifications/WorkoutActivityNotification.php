<?php

namespace App\Notifications;

use App\Enums\WorkoutStatus;
use App\Models\Workout;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Inviata agli amici accettati quando un utente inizia o completa un
 * workout (eventi MVP da docs/notifications.md). Non in coda
 * (ShouldQueue): l'infrastruttura di deploy attuale non ha un worker
 * dedicato in esecuzione, solo `php artisan serve` — vedi decisione in
 * AGENTS.md. Volume basso (notifiche solo tra amici), invio sincrono
 * accettabile per questa fase.
 */
class WorkoutActivityNotification extends Notification
{
    public function __construct(private readonly Workout $workout) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, self $notification): WebPushMessage
    {
        $username = $this->workout->user->username;
        $verb = $this->workout->status === WorkoutStatus::Active
            ? 'ha iniziato un allenamento'
            : 'ha completato un allenamento';

        return (new WebPushMessage)
            ->title('Gym Bros')
            ->icon('/pwa-192x192.png')
            ->body("{$username} {$verb}")
            ->data(['url' => '/workouts/'.$this->workout->id])
            ->options(['TTL' => 300]);
    }
}
