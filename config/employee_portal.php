<?php

return [
    'features' => [
        'document_services' => env('EMPLOYEE_PORTAL_DOCUMENT_SERVICES', false),
        'personal_data_update' => env('EMPLOYEE_PORTAL_PERSONAL_DATA_UPDATE', false),
        'benefits_summary' => env('EMPLOYEE_PORTAL_BENEFITS_SUMMARY', false),
    ],
];
