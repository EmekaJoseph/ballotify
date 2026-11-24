<?php

it('shows the launch checklist with live checks and the next step', function () {
    config(['services.paystack.secret_key' => 'sk_live_secret_value_never_shown']);

    $this->artisan('ballotify:launch')
        ->expectsOutputToContain('Live checks')
        ->expectsOutputToContain('Paystack live key')
        ->doesntExpectOutputToContain('sk_live_secret_value_never_shown')
        ->expectsOutputToContain('Before taking money')
        ->assertSuccessful();
});
