<?php

return [

    'default' => env('MAIL_MAILER', 'log'),

    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
            
            // SSL/TLS Configuration สำหรับแก้ปัญหา certificate
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'verify_peer' => env('MAIL_VERIFY_PEER', false),
            'verify_peer_name' => env('MAIL_VERIFY_PEER_NAME', false),
            'allow_self_signed' => env('MAIL_ALLOW_SELF_SIGNED', true),
            
            // Stream context options สำหรับ SSL
            'stream' => [
                'ssl' => [
                    'verify_peer' => env('MAIL_VERIFY_PEER', false),
                    'verify_peer_name' => env('MAIL_VERIFY_PEER_NAME', false),
                    'allow_self_signed' => env('MAIL_ALLOW_SELF_SIGNED', true),
                    'cafile' => env('MAIL_CAFILE'), // Path to CA bundle if available
                    'capath' => env('MAIL_CAPATH'), // Path to CA directory
                    'peer_name' => env('MAIL_PEER_NAME'), // Expected peer name
                    'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT,
                ]
            ]
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
        ],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example'),
    ],

];
