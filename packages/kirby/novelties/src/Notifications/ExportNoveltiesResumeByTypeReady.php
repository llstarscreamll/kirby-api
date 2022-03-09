<?php

namespace Kirby\Novelties\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Class ExportNoveltiesResumeByTypeReady.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ExportNoveltiesResumeByTypeReady extends Notification
{
    use Queueable;

    /**
     * @var string
     */
    public $fileUrl;

    /**
     * Created a new notification instance.
     */
    public function __construct(string $fileUrl)
    {
        $this->fileUrl = $fileUrl;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->greeting("Hola {$notifiable->first_name},")
            ->line('Tu reporte de novedades está listo!!')
            ->action('Descargar', $this->fileUrl);
    }
}
