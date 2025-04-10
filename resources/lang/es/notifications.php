<?php

return [
    'verify_email' => [
        'subject' => 'Verifica tu dirección de correo electrónico',
        'intro' => 'Haz clic en el botón a continuación para verificar tu dirección de correo electrónico.',
        'action' => 'Verificar correo electrónico',
        'outro' => 'Si no creaste una cuenta, no es necesario hacer nada más.',
    ],
    'reset_password' => [
        'subject' => 'Restablece tu contraseña',
        'intro' => 'Hemos recibido una solicitud para restablecer tu contraseña. Haz clic en el siguiente enlace para proceder.',
        'action' => 'Restablecer contraseña',
        'expiry' => 'Este enlace caducará en :count minutos.',
        'outro' => 'Si no solicitaste el restablecimiento de la contraseña, no es necesario realizar ninguna acción.',
    ],
    'greeting' => 'Hola :name',
    'salutation' => 'Saludos, ' . config('app.name'),
];
