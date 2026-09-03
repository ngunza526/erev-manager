<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Code de connexion a usage unique envoye par email (SEC-21 / B2).
 * Envoi synchrone : le code doit arriver avant que l'utilisateur ne saisisse
 * l'ecran OTP, on ne le met donc pas en file d'attente.
 */
class EmailOtpCodeNotification extends Notification
{
    public function __construct(
        private readonly string $code,
        private readonly int $expiresInMinutes,
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
        $name = trim((string) ($notifiable->name ?? '')) ?: 'utilisateur';

        return (new MailMessage)
            ->subject('Code de connexion '.config('app.name'))
            ->greeting('Bonjour '.$name.',')
            ->line('Votre code de connexion a usage unique :')
            ->line('**'.$this->code.'**')
            ->line('Ce code expire dans '.$this->expiresInMinutes.' minute(s).')
            ->line("Si vous n'avez pas tente de vous connecter, ignorez ce message et changez votre mot de passe.");
    }
}
