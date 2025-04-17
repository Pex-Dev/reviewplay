<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewReviewNotification extends Notification
{
    use Queueable;

    protected $user;
    protected $review;
    /**
     * Create a new notification instance.
     */
    public function __construct($user, $review)
    {
        $this->user = $user;
        $this->review = $review;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'user_avatar' => $this->user->image ?? null,
            'review_id' => $this->review->id,
            'review_score' => $this->review->score,
            'review_text' => $this->review->review,
            'game_id' => $this->review->game->id,
            'game_name' => $this->review->game->name,
            'game_background_image' => $this->review->game->background_image,
        ];
    }
}
