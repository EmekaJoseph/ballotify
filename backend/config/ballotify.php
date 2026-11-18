<?php

return [
    /*
    | Days after an event closes before voters' emails and phone numbers are
    | erased automatically (data minimisation, NDPR/GDPR). 0 disables it.
    | Runs daily via the scheduler: `php artisan schedule:work` (dev) or a
    | cron entry running `php artisan schedule:run` every minute (production).
    */
    'voter_contact_retention_days' => (int) env('VOTER_CONTACT_RETENTION_DAYS', 30),
];
