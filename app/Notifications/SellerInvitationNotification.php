<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SellerInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $sellerName,
        public string $acceptUrl
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Convite para a equipe — Salada Mix')
            ->greeting('Você recebeu um convite')
            ->line('Uma empresa convidou você para colaborar na operação do Salada Mix: '.$this->sellerName)
            ->action('Ver convite', $this->acceptUrl)
            ->line('O convite expira em 48 horas. Entre ou crie uma conta com este mesmo e-mail verificado.')
            ->line('Se não reconhece o convite, ignore esta mensagem.');
    }
}
