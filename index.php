<?php

$clientId = getenv('clientId') ?? $_GET['clientId'] ?? null;
$clientSecret = getenv('clientSecret') ?? $_GET['clientSecret'] ?? null;
if ($clientId === null || $clientSecret === null) {
    error('auth failure', "Need clientId and clientSecret env vars or parameters.\n");
}

$releaseName = getenv('release') ?: $argv[1] ?? $_GET['r'] ?? $_GET['release'] ?? null;
if ($releaseName === null) {
    error('which release?', "Need release env var, argument or parameter.\n");
}

$fortifyApi = 'https://api.emea.fortify.com';

// Get auth token. Fortify call it 'access_token'.
$url = $fortifyApi . '/oauth/token';
$postFields = implode('&', [
    'scope=api-tenant',
    'grant_type=client_credentials',
    'client_id=' . $clientId,
    'client_secret=' . $clientSecret,
]);
$request = "curl -s --data '$postFields' '$url'";
$response = shell_exec($request);
$decodedResponse = json_decode($response, true);
$authToken = $decodedResponse['access_token'] ?? null;
if ($authToken === null) {
    error('auth failure', "Failed authenticating. Are you auth credentials incorrect?\n");
}

// Get release id.
$url = $fortifyApi . "/api/v3/releases?filters=applicationName%3AROAR%2BreleaseName%3A$releaseName";
$request = "curl -s --header 'Authorization: Bearer $authToken' --header 'Accept: application/json' '$url'";
$response = shell_exec($request);
$decodedResponse = json_decode($response, true);
$releaseId = $decodedResponse['items'][0]['releaseId'] ?? null;
if ($releaseId === null) {
    error('release not found', "Failed fetching release id. $response", 'lightgrey');
}

// Get release details.
$url = $fortifyApi . "/api/v3/releases/$releaseId";
$request = "curl -s --header 'Authorization: Bearer $authToken' --header 'Accept: application/json' '$url'";
$response = shell_exec($request);
$release = json_decode($response, true);

$issues = [];
foreach(['critical', 'high', 'medium', 'low'] as $severity) {
    if ($release[$severity] > 0) {
        $issues[] = $release[$severity] . '%20' . $severity;
    }
}

if (count($issues) === 0) {
    $badgeString = 'fortify-passed-green';
} else {
    $badgeString = 'fortify-' . implode(',%20', $issues) . '-red';
}

redirectToBadge($badgeString);

function error($badgeText, $cliText, $colour = 'red') {
    if (php_sapi_name() == 'cli') {
        echo $cliText;
        exit;
    }
    redirectToBadge('fortify-' . rawurlencode($badgeText) . '-' . $colour);
}

function redirectToBadge($badgeString) {
    $url = "https://img.shields.io/badge/$badgeString.svg";
    if (php_sapi_name() == 'cli') {
        echo "Redirecting to $url\n";
        exit;
    }
    header("Location: $url");
    exit;
}
