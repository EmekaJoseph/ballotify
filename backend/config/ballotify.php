<?php

return [
    /*
    | Days after an event closes before voters' emails and phone numbers are
    | erased automatically (data minimisation, NDPR/GDPR). 0 disables it.
    | Runs daily via the scheduler: `php artisan schedule:work` (dev) or a
    | cron entry running `php artisan schedule:run` every minute (production).
    */
    /*
    | Languages of the public voting pages (the organiser picks the default,
    | voters can switch). Translations: frontend app/i18n, backend lang/*.json.
    */
    'languages' => ['en', 'yo', 'ha', 'ig', 'fr'],

    'voter_contact_retention_days' => (int) env('VOTER_CONTACT_RETENTION_DAYS', 30),

    /*
    | Billing (pay per event through Paystack). With BILLING_ENABLED=false every
    | event behaves as Pro: no limits, no payments (useful for private installs).
    | Prices and limits below are only the starting values: the platform admin
    | changes them from the Admin page, and those saved values win.
    */
    'billing' => [
        'enabled' => (bool) env('BILLING_ENABLED', true),
        'currency' => 'NGN',
        // Payment providers in order of preference; the next is used when one is down.
        'providers' => env('PAYMENT_PROVIDERS', 'paystack,flutterwave'),
        'defaults' => [
            // Pro, by size (per event). Each Pro event includes 1 SMS per voter it allows.
            'pro_small_price_naira' => 20000,
            'pro_small_max_voters' => 200,
            'pro_price_naira' => 45000,
            'free_max_active_events' => 1,
            'free_max_voters' => 30,
            'pro_max_voters' => 1000,
            'pro_large_price_naira' => 105000,
            'pro_large_max_voters' => 5000,
            // Extra SMS beyond what an event includes: prepaid credits, per SMS.
            'sms_price_naira' => 10,
            // Paid public voting: the platform's share of each vote payment.
            'paid_vote_fee_percent' => 10,
        ],
        // Smallest single payment for paid voting (Paystack charges have a floor).
        'min_vote_payment_naira' => 50,
    ],
];
