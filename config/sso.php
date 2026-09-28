<?php

return [
    'recent_auth_seconds' => 600,

    'providers' => [
        'google' => [
            'enabled' => (bool) env('SSO_GOOGLE_ENABLED', false),
            'label' => 'Google',
            'scopes' => ['openid', 'profile', 'email'],
            'trust_verified_email_claim' => true,
        ],
        'github' => [
            'enabled' => (bool) env('SSO_GITHUB_ENABLED', false),
            'label' => 'GitHub',
            'scopes' => ['read:user', 'user:email'],
            // Socialite does not expose an authoritative verified-email claim consistently
            // for GitHub, so an existing local account is never auto-linked by email.
            'trust_verified_email_claim' => false,
        ],
    ],
];

