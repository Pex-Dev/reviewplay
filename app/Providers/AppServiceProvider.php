<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Lang;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //Email personalizado para verificación de email
        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new MailMessage)
                ->greeting(Lang::get('notifications.greeting', ['name' => $notifiable->name]))
                ->subject(Lang::get('notifications.verify_email.subject'))
                ->line(Lang::get('notifications.verify_email.intro'))
                ->action(Lang::get('notifications.verify_email.action'), $url)
                ->line(Lang::get('notifications.verify_email.outro'))
                ->salutation(Lang::get('notifications.salutation'));
        });

        //Email personalizado para reestablecer contraseña
        ResetPassword::toMailUsing(function (object $notifiable, string $url) {
            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->greeting(Lang::get('notifications.greeting', ['name' => $notifiable->name]))
                ->subject(Lang::get('notifications.reset_password.subject'))
                ->line(Lang::get('notifications.reset_password.intro'))
                ->action(Lang::get('notifications.reset_password.action'), config('app.frontend_url') . "/password-reset/" . $url . "?email={$notifiable->getEmailForPasswordReset()}")
                ->line(Lang::get('notifications.reset_password.expiry', [
                    'count' => config('auth.passwords.' . config('auth.defaults.passwords') . '.expire')
                ]))
                ->line(Lang::get('notifications.reset_password.outro'))
                ->salutation(Lang::get('notifications.salutation'));
        });
    }
}
