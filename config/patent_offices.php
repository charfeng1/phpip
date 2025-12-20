<?php

/**
 * Patent office URLs and integration settings.
 *
 * Centralizes all patent office registry URLs for external linking.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Patent Office Registry URLs
    |--------------------------------------------------------------------------
    |
    | URLs for accessing patent/trademark registries by country code.
    | These are used to generate links to official registries from events.
    |
    | Placeholders:
    | - {number} - The application/publication/grant number
    | - {pubno} - The publication number
    | - {year} - The year portion
    |
    */
    'registries' => [
        // European Patent Office
        'EP' => [
            'application' => 'https://register.epo.org/espacenet/application?number=EP{number}',
            'ipfwretrieve' => 'https://register.epo.org/ipfwretrieve?apn=EP{number}.{kind}',
        ],

        // France (INPI)
        'FR' => [
            'patent' => 'https://data.inpi.fr/brevets/{country}{number}',
            'trademark' => 'https://data.inpi.fr/marques/{country}{number}',
        ],

        // United States (USPTO)
        'US' => [
            'application' => 'https://patft.uspto.gov/netacgi/nph-Parser?Sect1=PTO1&Sect2=HITOFF&d=PALL&p=1&u=%2Fnetahtml%2FPTO%2Fsrchnum.htm&r=1&f=G&l=50&s1={number}.PN.',
        ],

        // United Kingdom (UKIPO)
        'GB' => [
            'application' => 'http://www.ipo.gov.uk/p-ipsum/Case/ApplicationNumber/{country}{number}',
        ],

        // European Union (EUIPO - Trademarks)
        'EM' => [
            'trademark' => 'https://euipo.europa.eu/eSearch/#details/trademarks/{number}',
        ],

        // WIPO
        'WO' => [
            'application' => 'https://patentscope.wipo.int/search/en/detail.jsf?docId=WO{number}',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Espacenet (Worldwide Patent Database)
    |--------------------------------------------------------------------------
    |
    | General worldwide patent search URL.
    |
    */
    'espacenet' => [
        'biblio' => 'http://worldwide.espacenet.com/publicationDetails/biblio?CC={country}&NR={number}&KC={kind}',
    ],

    /*
    |--------------------------------------------------------------------------
    | EPO OPS (Open Patent Services) API
    |--------------------------------------------------------------------------
    |
    | Configuration for the EPO OPS API integration.
    | Credentials should be set in environment variables.
    |
    */
    'ops' => [
        'base_url' => env('OPS_BASE_URL', 'https://ops.epo.org/3.2'),
        'auth_url' => env('OPS_AUTH_URL', 'https://ops.epo.org/3.2/auth/accesstoken'),
    ],

    /*
    |--------------------------------------------------------------------------
    | PCT Countries
    |--------------------------------------------------------------------------
    |
    | Countries that are part of the PCT (Patent Cooperation Treaty) system.
    | These don't have independent renewal tracking.
    |
    */
    'pct_countries' => ['EP', 'WO', 'EM', 'OA'],
];
