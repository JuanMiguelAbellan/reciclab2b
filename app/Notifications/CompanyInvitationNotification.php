<?php

namespace App\Notifications;

use App\Models\CompanyInvitation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class CompanyInvitationNotification extends Notification
{
    public function __construct(private readonly CompanyInvitation $invitation) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'invitations.accept',
            now()->addDays(7),
            ['invitation' => $this->invitation->id],
        );

        return (new MailMessage)
            ->subject("Te han invitado a unirte a {$this->invitation->company->trade_name} en ReciclaB2B")
            ->greeting('¡Hola!')
            ->line("{$this->invitation->invitedBy->full_name} te ha invitado a unirte a la empresa \"{$this->invitation->company->trade_name}\" en ReciclaB2B.")
            ->action('Aceptar invitación', $url)
            ->line('El enlace caduca en 7 días. Si no esperabas esta invitación, puedes ignorar este correo.');
    }
}
