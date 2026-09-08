<?php

namespace App\Notifications;

use App\Enums\UserStatus;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserStatusChangedNotification extends Notification
{
    public function __construct(private readonly UserStatus $status) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return match ($this->status) {
            UserStatus::Approved => (new MailMessage)
                ->subject('Tu cuenta en ReciclaB2B ha sido aprobada')
                ->greeting('¡Buenas noticias!')
                ->line('Tu cuenta en ReciclaB2B ha sido aprobada. Ya puedes iniciar sesión y empezar a usar la plataforma.')
                ->action('Iniciar sesión', url('/login')),
            UserStatus::Rejected => (new MailMessage)
                ->subject('Tu solicitud de acceso a ReciclaB2B ha sido rechazada')
                ->greeting('Hola,')
                ->line('Tu solicitud de acceso a ReciclaB2B ha sido rechazada.')
                ->line('Si crees que es un error, contacta con nosotros respondiendo a este correo.'),
            UserStatus::Blocked => (new MailMessage)
                ->subject('Tu cuenta en ReciclaB2B ha sido bloqueada')
                ->greeting('Hola,')
                ->line('Tu cuenta en ReciclaB2B ha sido bloqueada y ya no puedes acceder a la plataforma.')
                ->line('Si crees que es un error, contacta con el administrador respondiendo a este correo.'),
            UserStatus::Pending, UserStatus::Inactive => (new MailMessage)
                ->subject('Cambio de estado en tu cuenta de ReciclaB2B')
                ->line("El estado de tu cuenta ha cambiado a: {$this->status->label()}."),
        };
    }
}
