<?php

declare(strict_types=1);

return [
    'common' => [
        'greeting_named' => 'Hi :name!',
        'salutation' => 'Best regards',
        'link_expiry_minutes' => 'This link is valid for :minutes minutes.',
    ],
    'reset_password' => [
        'subject' => 'Reset your password',
        'intro' => 'We received a request to set a new password for your account.',
        'action' => 'Set a new password',
        'outro' => 'If you did not request this change, ignore this message. Your password will stay the same.',
    ],
    'verify_email' => [
        'subject' => 'Confirm your email address',
        'intro' => 'Thank you for creating an account. Before you post your first ad, confirm your email address.',
        'action' => 'Confirm email address',
        'outro' => 'If you did not create this account, ignore this message — nothing will happen.',
    ],
];
