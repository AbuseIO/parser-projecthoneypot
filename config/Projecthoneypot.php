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
            'class'     => 'HARVESTING',
            'type'      => 'ABUSE',
            'enabled'   => true,
            'fields'    => [
                'ip',
            ],
        ],
        'Email' => [
            'class'     => 'SPAM',
            'type'      => 'ABUSE',
            'enabled'   => true,
            'fields'    => [
                'ip',
            ],
        ],
        'Dictionary' => [
            'class'     => 'DICTIONARY_ATTACK',
            'type'      => 'ABUSE',
            'enabled'   => true,
            'fields'    => [
                'ip',
            ],
        ],
        'Comment' => [
            'class'     => 'COMMENT_SPAM',
            'type'      => 'ABUSE',
            'enabled'   => true,
            'fields'    => [
                'ip',
            ],
        ],
        'Potentially' => [
            'class'     => 'HARVESTING',
            'type'      => 'ABUSE',
            'enabled'   => true,
            'fields'    => [
                'ip',
            ],
        ],
    ],
];
