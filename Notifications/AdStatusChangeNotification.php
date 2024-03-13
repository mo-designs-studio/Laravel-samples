<?php

namespace App\Notifications;

use App\Model\User\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class AdStatusChangeNotification extends Notification
{
    use Queueable;

    public $publisherId;
    private $propertyAd;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($propertyAd, $publisherId)
    {
        $this->propertyAd = $propertyAd;
        $this->publisherId = $publisherId;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database', 'broadCast'];
    }

    public function broadcastType(): string
    {
        return 'adStatusChange';
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    public function toDatabase($notifiable)
    {
        return [
            'notificationType' => 'adStatus',
            'adId' => $this->propertyAd->uuid,
            'adTitle' => $this->propertyAd->title,
            'adImage' => $this->propertyAd->images->where('use', 'default')->pluck('image_url')->first(),
            'status' => $this->propertyAd->ad_status,
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'id' => Str::uuid(),
            "read_at" => null,
            "notified_since" => Carbon::now()->subSeconds(1)->diffForHumans(Carbon::now(), CarbonInterface::DIFF_ABSOLUTE,),
            'data' => [
                'notificationType' => 'adStatus',
                'adId' => $this->propertyAd->uuid,
                'adTitle' => $this->propertyAd->title,
                'adImage' => $this->propertyAd->images->where('use', 'default')->pluck('image_url')->first(),
                'status' => $this->propertyAd->ad_status,
            ],
        ];
    }

}
