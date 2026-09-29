<?php

declare(strict_types=1);

// SSH-only utility. The database secret arrives via STDIN, never CLI args.
umask(0077);
$root = dirname(__DIR__);
$shared = $root.'/shared';
if (!is_dir($shared) && !mkdir($shared, 0700, true) && !is_dir($shared)) {
    fwrite(STDERR, "Cannot create private environment directory.\n");
    exit(1);
}
$password = stream_get_contents(STDIN);
if ($password === false || $password === '' || strpbrk($password, "\r\n\0") !== false) {
    fwrite(STDERR, "Database password is empty or malformed.\n");
    exit(1);
}
$path = $shared.'/.env';
$key = 'base64:'.base64_encode(random_bytes(32));
if (is_file($path)) {
    $existing = file_get_contents($path);
    if ($existing === false || !preg_match('/^APP_KEY=(base64:[A-Za-z0-9+\/=]+)$/m', $existing, $match)) {
        fwrite(STDERR, "Existing environment has no usable APP_KEY. Refusing rotation.\n");
        exit(1);
    }
    $key = $match[1];
}
$settings = [
    'APP_NAME="Salada Mix HML"',
    'APP_ENV=staging',
    'APP_KEY='.$key,
    'APP_DEBUG=false',
    'APP_URL=https://ivory-rook-276202.hostingersite.com',
    'APP_TIMEZONE=America/Sao_Paulo',
    'APP_LOCALE=pt_BR',
    'APP_FALLBACK_LOCALE=en',
    'LOG_CHANNEL=stack',
    'LOG_LEVEL=warning',
    'DB_CONNECTION=mariadb',
    'DB_HOST=localhost',
    'DB_PORT=3306',
    'DB_DATABASE=u904890479_salada_hml',
    'DB_USERNAME=u904890479_sm_hml',
    'DB_PASSWORD=',
    // Encoding handles special characters, not secrecy. File is mode 0600.
    'DB_PASSWORD_B64='.base64_encode($password),
    'SESSION_DRIVER=database',
    'SESSION_ENCRYPT=true',
    'SESSION_SECURE_COOKIE=true',
    'SESSION_COOKIE=salada_mix_hml_session',
    'SESSION_SAME_SITE=lax',
    'CACHE_STORE=database',
    'QUEUE_CONNECTION=sync',
    'FILESYSTEM_DISK=local',
    'MAIL_MAILER=log',
    'MAIL_FROM_ADDRESS=no-reply@example.test',
    'MAIL_FROM_NAME="Salada Mix HML"',
    'BROADCAST_CONNECTION=log',
    'MARKETPLACE_CHECKOUT_ENABLED=false',
    'MARKETPLACE_ORDER_DRAFTS_ENABLED=false',
    'PAYMENTS_PROVIDER=none',
    'SSO_GOOGLE_ENABLED=false',
    'SSO_GITHUB_ENABLED=false',
    'PULSE_ENABLED=false',
    'TELESCOPE_ENABLED=false',
    'NIGHTWATCH_ENABLED=false',
];
$temp = tempnam($shared, '.env-new-');
if ($temp === false || !chmod($temp, 0600) || file_put_contents($temp, implode("\n", $settings)."\n", LOCK_EX) === false || !rename($temp, $path)) {
    fwrite(STDERR, "Could not safely save environment file.\n");
    exit(1);
}
chmod($path, 0600);
fwrite(STDOUT, "Private HML environment prepared; APP_KEY preserved on retry.\n");
