<?php

return [
    'key' => env('FLARE_KEY'),

    'reporting' => [
        'anonymize_ips' => true,
        'collect_git_information' => false,
        'report_queries' => true,
        'maximum_number_of_collected_queries' => 200,
        'report_query_bindings' => true,
        'report_view_data' => true,
        'grouping_type' => null,
        'report_logs' => true,
        'maximum_number_of_collected_logs' => 200,
        'censor_request_body_fields' => ['password'],
    ],

    'send_logs_as_events' => true,

    'censor_request_body_fields' => ['password'],
];
