<?php

return [
    'host' => env('IMAP_HOST', 'imap.hostinger.com'),
    'port' => (int) env('IMAP_PORT', 993),
    'encryption' => env('IMAP_ENCRYPTION', 'ssl'),
    'validate_cert' => filter_var(env('IMAP_VALIDATE_CERT', true), FILTER_VALIDATE_BOOL),
    'username' => env('IMAP_USERNAME', env('MAIL_USERNAME')),
    'password' => env('IMAP_PASSWORD', env('MAIL_PASSWORD')),
    'folder' => env('IMAP_FOLDER', 'INBOX'),
    'mark_as_read' => filter_var(env('IMAP_MARK_AS_READ', false), FILTER_VALIDATE_BOOL),
];
