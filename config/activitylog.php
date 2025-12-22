<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Activity Log Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable activity logging globally
    |
    */
    'enabled' => env('ACTIVITY_LOG_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Log Retention Days
    |--------------------------------------------------------------------------
    |
    | Number of days to keep activity logs before automatic cleanup
    |
    */
    'retention_days' => env('ACTIVITY_LOG_RETENTION_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | Log View Activities
    |--------------------------------------------------------------------------
    |
    | Whether to log view/read operations (can generate a lot of logs)
    |
    */
    'log_views' => env('ACTIVITY_LOG_VIEWS', false),

    /*
    |--------------------------------------------------------------------------
    | Suspicious Login Attempts
    |--------------------------------------------------------------------------
    |
    | Number of login attempts from same IP before flagging as suspicious
    |
    */
    'suspicious_login_attempts' => env('ACTIVITY_LOG_SUSPICIOUS_LOGIN_ATTEMPTS', 5),

    /*
    |--------------------------------------------------------------------------
    | Suspicious Login Window
    |--------------------------------------------------------------------------
    |
    | Time window in seconds for counting suspicious login attempts
    |
    */
    'suspicious_login_window' => env('ACTIVITY_LOG_SUSPICIOUS_LOGIN_WINDOW', 300),

    /*
    |--------------------------------------------------------------------------
    | Suspicious Delete Threshold
    |--------------------------------------------------------------------------
    |
    | Number of delete operations before flagging as suspicious
    |
    */
    'suspicious_delete_threshold' => env('ACTIVITY_LOG_SUSPICIOUS_DELETE_THRESHOLD', 10),
];
