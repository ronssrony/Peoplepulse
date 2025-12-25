<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Office Start Time
    |--------------------------------------------------------------------------
    |
    | The official start time of the workday. Used to determine late arrivals.
    | Format: HH:MM (24-hour format)
    |
    */
    'office_start_time' => env('OFFICE_START_TIME', '09:00'),

    /*
    |--------------------------------------------------------------------------
    | Late Grace Period
    |--------------------------------------------------------------------------
    |
    | Number of minutes after office start time before marking as late.
    |
    */
    'late_grace_minutes' => env('LATE_GRACE_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Office End Time
    |--------------------------------------------------------------------------
    |
    | The official end time of the workday. Used to determine early exits.
    | Format: HH:MM (24-hour format)
    |
    */
    'office_end_time' => env('OFFICE_END_TIME', '18:00'),

    /*
    |--------------------------------------------------------------------------
    | Default Break Duration
    |--------------------------------------------------------------------------
    |
    | Default break duration in minutes to subtract from gross hours.
    |
    */
    'default_break_minutes' => env('DEFAULT_BREAK_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Default Weekend Days
    |--------------------------------------------------------------------------
    |
    | Default weekend days for new users if not specified.
    |
    */
    'default_weekend_days' => ['saturday', 'sunday'],

    /*
    |--------------------------------------------------------------------------
    | Weekend Options
    |--------------------------------------------------------------------------
    |
    | Available weekend configurations that can be assigned to users.
    |
    */
    'weekend_options' => [
        'fri_sat' => ['friday', 'saturday'],
        'sat_sun' => ['saturday', 'sunday'],
        'fri_only' => ['friday'],
        'sat_only' => ['saturday'],
        'sun_only' => ['sunday'],
    ],
];
