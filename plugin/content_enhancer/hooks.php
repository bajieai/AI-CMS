<?php

return [
    'content.before_save' => [
        'callback' => 'ContentEnhancerPlugin@beforeSave',
        'type'     => 'filter',
        'priority' => 20,
    ],
    'content.after_display' => [
        'callback' => 'ContentEnhancerPlugin@afterDisplay',
        'type'     => 'filter',
        'priority' => 15,
    ],
];
