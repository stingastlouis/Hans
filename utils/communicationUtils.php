<?php

function redirectBackWithMessage(string $type, string $customMessage)
{
    $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    $type = strtolower($type) === 'success' ? 'success' : 'error';

    $parsedUrl = parse_url($referer);
    $base = $parsedUrl['path'];
    $query = [];

    if (!empty($parsedUrl['query'])) {
        parse_str($parsedUrl['query'], $query);
    }

    $query[$type] = 1;
    $query['msg'] = $customMessage
        ? urlencode($customMessage)
        : urlencode($type === 'success' ? 'Action completed successfully.' : 'An error occurred.');

    $redirectUrl = $base . '?' . http_build_query($query);

    header("Location: $redirectUrl");
    exit;
}
