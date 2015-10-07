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
        'aliases'       => [
            'H'         => 'Harvesting',
            'S'         => 'Email',
            'D'         => 'Dictionary',
            'C'         => 'Comment',
            'R'         => 'Potentially',
        ],
    ],

    'feeds' => [
        'Harvesting' => [
            'class'     => 'Harvesting',
            'type'      => 'Abuse',
            'enabled'   => true,
            'fields'    => [
                'ip',
            ],
        ],
        'Email' => [
            'class'     => 'SPAM',
            'type'      => 'Abuse',
            'enabled'   => true,
            'fields'    => [
                'ip',
            ],
        ],
        'Dictionary' => [
            'class'     => 'Dictionary attack',
            'type'      => 'Abuse',
            'enabled'   => true,
            'fields'    => [
                'ip',
            ],
        ],
        'Comment' => [
            'class'     => 'Comment Spam',
            'type'      => 'Abuse',
            'enabled'   => true,
            'fields'    => [
                'ip',
            ],
        ],
        'Potentially' => [
            'class'     => 'Harvesting',
            'type'      => 'Abuse',
            'enabled'   => true,
            'fields'    => [
                'ip',
            ],
        ],
    ],
];
