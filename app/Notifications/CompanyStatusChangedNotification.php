<?php

namespace App\Notifications;

use App\Enums\CompanyStatus;
use App\Models\Company;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyStatusChangedNotification extends Notification
{
    public function __construct(
        private readonly Company $company,
        private readonly CompanyStatus $status,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $this->company->trade_name;

        return match ($this->status) {
            CompanyStatus::Approved => (new MailMessage)
                ->subject("Tu empresa \"{$name}\" ha sido aprobada en ReciclaB2B")
                ->greeting('¡Buenas noticias!')
                ->line("Tu empresa \"{$name}\" ha sido aprobada en ReciclaB2B. Ya podéis publicar ofertas y operar en la plataforma.")
                ->action('Ir a la plataforma', url('/dashboard')),
            CompanyStatus::Rejected => (new MailMessage)
                ->subject("La solicitud de alta de \"{$name}\" ha sido rechazada")
                ->greeting('Hola,')
                ->line("La solicitud de alta de \"{$name}\" en ReciclaB2B ha sido rechazada.")
                ->line('Si crees que es un error, contacta con nosotros respondiendo a este correo.'),
            CompanyStatus::Blocked => (new MailMessage)
                ->subject("Tu empresa \"{$name}\" ha sido bloqueada en ReciclaB2B")
                ->greeting('Hola,')
                ->line("Tu empresa \"{$name}\" ha sido bloqueada. Sus ofertas dejan de ser visibles y no podéis operar en la plataforma.")
                ->line('Si crees que es un error, contacta con el administrador respondiendo a este correo.'),
            CompanyStatus::Pending, CompanyStatus::Inactive => (new MailMessage)
                ->subject("Cambio de estado en \"{$name}\"")
                ->line("El estado de \"{$name}\" ha cambiado a: {$this->status->label()}."),
        };
    }
}
