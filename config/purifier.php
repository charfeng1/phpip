<?php

return [
    'default' => 'main',

    'settings' => [
        'main' => [
            'HTML.SafeIframe' => true,
            'HTML.SafeObject' => true,
            'HTML.Allowed' => 'p,b,strong,em,ul,ol,li,br,a[href|title|target],span,table,thead,tbody,tr,th,td',
            'Attr.AllowedFrameTargets' => ['_blank', '_self'],
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty' => true,
        ],
    ],
];
