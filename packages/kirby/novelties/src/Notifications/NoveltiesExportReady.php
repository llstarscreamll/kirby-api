<?php

namespace Kirby\Novelties\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Class NoveltiesExportReady.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class NoveltiesExportReady extends Notification
{
    use Queueable;

    /**
     * @var string
     */
    public $exportFilePath;

    /**
     * Created a new notification instance.
     *
     * @param string $exportFilePath
     */
    public function __construct(string $exportFilePath)
    {
        $this->exportFilePath = $exportFilePath;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed   $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed                                            $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->greeting("Hola {$notifiable->first_name},")
            ->line('La exportación de datos de novedades está listo!!')
            ->action('Descargar', asset("storage/$this->exportFilePath"));
    }
}
