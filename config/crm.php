<?php

return [
    'pid_start' => 5000,

    'visa_types' => [
        'Study visa',
        'Visitor Visa',
        'PR',
        'Spouse open Work Permit',
        'Family Sponsorship Permit',
        'IELTS',
        'Spoken English',
        'TOEFL',
        'Life Skills',
        'PTE',
        'Closed Work Permit(LMIA)',
        'Super Visa',
        'Duolingo',
        'Interview',
        'CAIPS/ATIPS',
        'WES',
        'Useless Lead',
        'work permit',
    ],

    'process_timelines' => [
        'Study visa' => [
            ['key' => 'offer-letter-applied', 'label' => 'Offer letter applied'],
            ['key' => 'col-received', 'label' => 'COL received'],
            ['key' => 'arranging-financials', 'label' => 'Arranging financials'],
            ['key' => 'financials-sent-to-uni', 'label' => 'Financials sent to uni'],
            ['key' => 'preparing-interview', 'label' => 'Preparing Interview'],
            ['key' => 'uncol-received', 'label' => 'UnCOL received'],
            ['key' => 'file-lodged', 'label' => 'File lodged'],
            ['key' => 'visa-approved', 'label' => 'Visa approved'],
            ['key' => 'video-shot', 'label' => 'Video shot'],
        ],
    ],

    'statuses_requiring_follow_up' => [
        'interested',
        'wv again',
        'pursuing ielts/pte',
        'arranging docs',
        'in process',
    ],

    'statuses_no_follow_up' => [
        'not eligible',
        'plan drop',
    ],

    'customer_statuses' => [
        'assigned',
        'interested',
        'pursuing ielts/pte',
        'in process',
        'not eligible',
        'plan drop',
        'will visit',
        'jfi',
    ],

    'remark_statuses' => [
        'interested',
        'pursuing ielts/pte',
        'in process',
        'not eligible',
        'plan drop',
        'will visit',
        'jfi',
    ],

    'visiting_client_statuses' => [
        'will visit',
        'interested',
    ],

    'telecaller_statuses' => [
        'will visit',
        'interested',
    ],

    'genders' => ['male', 'female'],

    'marital_statuses' => ['single', 'married', 'divorced'],

    'gap_options' => [
        'No GAP',
        '1 year',
        '2 years',
        '3 years',
        '4 years',
        '5 years',
        '6+ years',
    ],

    'english_exams' => [
        'IELTS',
        'PTE',
        'CELPIP',
        'TOEFL',
        'Life Skills',
        'Duolingo',
        'UKVI',
    ],

    'google_chat_notification_types' => [
        'remarks' => [
            'label' => 'Remarks and status updates',
            'description' => 'Messages sent when someone adds a customer remark or updates status from remarks.',
        ],
        'bell_new_case_assigned' => [
            'label' => 'Bell: New case assigned',
            'description' => 'Google Chat copy of bell notifications for newly assigned cases.',
        ],
        'bell_customer_updated' => [
            'label' => 'Bell: Customer updated',
            'description' => 'Google Chat copy of bell notifications when customer fields are changed.',
        ],
        'bell_remark_tagged' => [
            'label' => 'Bell: User tagged in remark',
            'description' => 'Google Chat copy of bell notifications created by @mentions in remarks.',
        ],
        'bell_follow_up_reminder' => [
            'label' => 'Bell: Follow-up reminder',
            'description' => 'Google Chat copy of bell notifications for follow-up reminders.',
        ],
        'bell_other' => [
            'label' => 'Bell: Other notifications',
            'description' => 'Google Chat copy of any other bell notification not listed above.',
        ],
        'activity_logs' => [
            'label' => 'Activity logs',
            'description' => 'Google Chat messages generated from CRM activity logs, such as customer, document, fee, and admin changes.',
        ],
    ],

    'countries' => [
        'canada' => 'Canada',
        'UK' => 'UK',
        'india' => 'India',
        'aus' => 'Australia',
        'nz' => 'New Zealand',
        'germany' => 'Germany',
        'france' => 'France',
        'cyprus' => 'Cyprus',
        'finland' => 'Finland',
        'spain' => 'Spain',
        'greece' => 'Greece',
        'malta' => 'Malta',
        'japan' => 'Japan',
        'Mauritius' => 'Mauritius',
        'moldova' => 'Moldova',
    ],

    'country_iso' => [
        'canada' => 'ca',
        'UK' => 'gb',
        'india' => 'in',
        'aus' => 'au',
        'nz' => 'nz',
        'germany' => 'de',
        'france' => 'fr',
        'cyprus' => 'cy',
        'finland' => 'fi',
        'spain' => 'es',
        'greece' => 'gr',
        'malta' => 'mt',
        'japan' => 'jp',
        'Mauritius' => 'mu',
        'moldova' => 'md',
    ],
];
