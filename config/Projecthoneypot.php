<?php

return [
    'parser' => [
        'name'          => 'Project Honey Pot',
        'enabled'       => true,
        'sender_map'    => [
            '/monitor-bounce@projecthoneypot.org/',
        ],
        'body_map'      => [
            //
        ],
    ],

    'feeds' => [
        'H' => [
            'class'     => 'Harvesting',
            'type'      => 'Abuse',
            'enabled'   => true,
            'fields'    => [
                'Source',
                'Date',
            ],
        ],
        'S' => [
            'class'     => 'SPAM',
            'type'      => 'Abuse',
            'enabled'   => true,
            'fields'    => [
                'Source',
                'Date',
            ],
        ],
        'D' => [
            'class'     => 'Dictionary attack',
            'type'      => 'Abuse',
            'enabled'   => true,
            'fields'    => [
                'Source',
                'Date',
            ],
        ],
        'C' => [
            'class'     => 'Comment Spam',
            'type'      => 'Abuse',
            'enabled'   => true,
            'fields'    => [
                'Source',
                'Date',
            ],
        ],
        'R' => [
            'class'     => 'Rule Breaker',
            'type'      => 'Abuse',
            'enabled'   => true,
            'fields'    => [
                'Source',
                'Date',
            ],
        ],
    ],
];
