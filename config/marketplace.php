<?php

return [
    'checkout_enabled' => (bool) env('MARKETPLACE_CHECKOUT_ENABLED', false),
    'payments_provider' => env('PAYMENTS_PROVIDER', 'none'),
];

