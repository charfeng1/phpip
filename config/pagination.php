<?php

/**
 * Pagination configuration for different list views.
 *
 * Centralizes all pagination limits to avoid magic numbers scattered across controllers.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Pagination Limit
    |--------------------------------------------------------------------------
    |
    | The default number of items per page when no specific limit is configured.
    |
    */
    'default' => 21,

    /*
    |--------------------------------------------------------------------------
    | Entity-Specific Pagination Limits
    |--------------------------------------------------------------------------
    |
    | Specific limits for different entity list views.
    |
    */
    'actors' => 21,
    'users' => 21,
    'countries' => 21,
    'rules' => 21,
    'event_names' => 21,
    'matters' => 25,
    'tasks' => 18,
    'audit_logs' => 50,
    'template_members' => 21,

    /*
    |--------------------------------------------------------------------------
    | Autocomplete Limits
    |--------------------------------------------------------------------------
    |
    | Number of results to show in autocomplete dropdowns.
    |
    */
    'autocomplete' => [
        'default' => 15,
        'actors' => 15,
        'matters' => 10,
        'users' => 15,
        'countries' => 15,
        'classifiers' => 15,
        'events' => 15,
        'roles' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Batch Operation Limits
    |--------------------------------------------------------------------------
    |
    | Limits for batch/bulk operations.
    |
    */
    'batch' => [
        'actor_pivot' => 50,
        'classifier' => 30,
    ],
];
