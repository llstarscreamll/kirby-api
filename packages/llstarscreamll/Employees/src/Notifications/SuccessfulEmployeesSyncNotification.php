<?php

namespace llstarscreamll\Employees\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Class SuccessfulEmployeesSyncNotification.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SuccessfulEmployeesSyncNotification extends Notification
{
    /**
     * @var int
     */
    private $employeesSynced;

    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(int $employeesSynced)
    {
        $this->employeesSynced = $employeesSynced;
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
            ->subject('Sincronización de empleados exitosa')
            ->greeting("Hola {$notifiable->first_name}!")
            ->line("La tarea de sincronización de empleados ha sido "
                ."finalizada exitosamente, fueron procesados "
                ."**{$this->employeesSynced}** registros.")
            ->salutation("Saludos, ".config("app.name").".");
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
