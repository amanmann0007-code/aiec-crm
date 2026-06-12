<?php

return [
    'name' => env('COMPANY_NAME', 'AIEC Institute'),
    'crm_name' => env('COMPANY_CRM_NAME', 'AIEC CRM'),
    'tagline' => env('COMPANY_TAGLINE', 'Customer Relationship Management'),
    'subtitle' => env('COMPANY_SUBTITLE', 'Study Abroad Lally Infosys'),
    'logo_path' => env('COMPANY_LOGO_PATH', 'images/aiec-logo.png'),
    'icon_path' => env('COMPANY_ICON_PATH', 'images/aiec-icon.svg'),
    'address_lines' => array_values(array_filter([
        env('COMPANY_ADDRESS_LINE1', ''),
        env('COMPANY_ADDRESS_LINE2', ''),
        env('COMPANY_ADDRESS_LINE3', ''),
    ])),
    'phone' => env('COMPANY_PHONE', ''),
    'email' => env('COMPANY_EMAIL', ''),
    'website' => env('COMPANY_WEBSITE') ?: env('APP_URL', ''),
];
