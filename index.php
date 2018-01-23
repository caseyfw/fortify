<?php

$clientId = getParameter('clientId');
$clientSecret = getParameter('clientSecret');
if ($clientId === null || $clientSecret === null) {
    error('auth failure', "Need clientId and clientSecret env vars or parameters.\n");
}
debug("Client ID: $clientId");
debug("Client secret: $clientSecret");

$releaseName = getParameter('release') ?? $argv[1] ?? null;
if ($releaseName === null) {
    error('which release?', "Need release env var, argument or parameter.\n");
}
debug("Release name: $releaseName");

$cacheDir = getParameter('cacheDir', 'cache');
if (!empty($cacheDir) && is_dir($cacheDir)) {
    debug("Cache dir: $cacheDir");
    $cacheFile = realpath($cacheDir) . '/' . preg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $releaseName);
    debug("Cache file: $cacheFile");
    if (file_exists($cacheFile)) {
        debug("Cache hit!");
        redirectToBadge(file_get_contents($cacheFile));
    }
    debug("Cache miss.");
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
debug("Authenticated. Auth token: $authToken");

// Get release id.
$url = $fortifyApi . "/api/v3/releases?filters=applicationName%3AROAR%2BreleaseName%3A$releaseName";
$request = "curl -s --header 'Authorization: Bearer $authToken' --header 'Accept: application/json' '$url'";
$response = shell_exec($request);
$decodedResponse = json_decode($response, true);
$releaseId = $decodedResponse['items'][0]['releaseId'] ?? null;
if ($releaseId === null) {
    error('release not found', "Failed fetching release id. $response", 'lightgrey');
}
debug("Release ID: $releaseId");

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
    debug("No issues.");
} else {
    $badgeString = 'fortify-' . implode(',%20', $issues) . '-red';
    debug("Issues: " . implode(', ', $issues));
}

// Create cache entry if possible.
if (isset($cacheFile) && is_writable(realpath($cacheDir))) {
    debug("Writing '$badgeString' to $cacheFile");
    file_put_contents($cacheFile, $badgeString);
}
redirectToBadge($badgeString);

function error($badgeString, $cliText, $colour = 'red') {
    if (php_sapi_name() == 'cli') {
        echo $cliText;
        exit;
    }
    redirectToBadge('fortify-' . rawurlencode($badgeString) . '-' . $colour);
}

function debug($text) {
    if (php_sapi_name() == 'cli') {
        echo $text . "\n";
    }
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

function getParameter($key, $default = null) {
    // If an env var is set with the 'File' suffix, load param from file.
    $keyFile = $key . 'File';
    if (!empty(getenv($keyFile))) {
        return file_exists($keyFile) ? file_get_contents($keyFile) : $default;
    }

    // Getenv returns an empty string for non-existant variables (Y U NO null?).
    return getenv($key) ?: $_GET[$key] ?? $default;
}
