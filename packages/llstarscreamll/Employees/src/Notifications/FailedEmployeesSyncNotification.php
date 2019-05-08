<?php

namespace llstarscreamll\Employees\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Class FailedEmployeesSyncNotification.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class FailedEmployeesSyncNotification extends Notification
{
    /**
     * @var string
     */
    private $errorMessage;

    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $errorMessage)
    {
        $this->errorMessage = $errorMessage;
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
            ->error()
            ->subject('Sincronización de empleados fallida')
            ->greeting("Hola {$notifiable->first_name}!")
            ->line('La tarea de sincronización de empleados ha devuelto el siguiente error:')
            ->line("**{$this->errorMessage}**")
            ->salutation('Saludos, '.config('app.name').'.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed   $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
