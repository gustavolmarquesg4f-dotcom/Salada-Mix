<?php

return [
    'order_drafts_enabled' => (bool) env('MARKETPLACE_ORDER_DRAFTS_ENABLED', false),
    'draft_reservation_minutes' => (int) env('MARKETPLACE_DRAFT_RESERVATION_MINUTES', 15),
    'checkout_enabled' => (bool) env('MARKETPLACE_CHECKOUT_ENABLED', false),
    'payments_provider' => env('PAYMENTS_PROVIDER', 'none'),
];
