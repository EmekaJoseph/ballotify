<?php

return [
    /*
    | Days after an event closes before voters' emails and phone numbers are
    | erased automatically (data minimisation, NDPR/GDPR). 0 disables it.
    | Runs daily via the scheduler: `php artisan schedule:work` (dev) or a
    | cron entry running `php artisan schedule:run` every minute (production).
    */
    'voter_contact_retention_days' => (int) env('VOTER_CONTACT_RETENTION_DAYS', 30),

    /*
    | Country calling code used to turn local phone numbers ("0801...") into
    | international ones for WhatsApp links, e.g. 234 for Nigeria.
    */
    'default_country_code' => env('DEFAULT_COUNTRY_CODE', '234'),
];
