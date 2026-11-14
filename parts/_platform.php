<?php
/**
 * SONIC PLATFORM (BATCH 8): rate-limit per-IP + ETag/304 untuk api/*.php.
 * Include berulang aman (function_exists guard). Butuh INDEX_AUTH + $sysconf.
 */
if (!defined('INDEX_AUTH')) { http_response_code(403); exit; }

if (!function_exists('sonic_client_ip')) {
    function sonic_client_ip() {
        return preg_replace('/[^0-9a-fA-F:.]/', '', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }
}
if (!function_exists('sonic_rate_limit')) {
    /** Sliding-window per-IP. Bila lewat batas: 429 + Retry-After lalu exit. */
    function sonic_rate_limit($bucket, $limit = 60, $window = 60) {
        $dir = sys_get_temp_dir() . '/sonic_rl';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $file = $dir . '/' . md5((defined('SB') ? SB : '') . $bucket . '|' . sonic_client_ip()) . '.json';
        $now = time(); $hits = [];
        if (is_file($file)) {
            $d = json_decode((string)@file_get_contents($file), true);
            if (is_array($d)) $hits = array_values(array_filter($d, fn($t) => ($now - (int)$t) < $window));
        }
        if (count($hits) >= $limit) {
            $retry = max(1, $window - ($now - (int)$hits[0]));
            http_response_code(429);
            header('Retry-After: ' . $retry);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'rate_limited', 'retry_after' => $retry]);
            exit;
        }
        $hits[] = $now;
        @file_put_contents($file, json_encode($hits), LOCK_EX);
        return true;
    }
}
if (!function_exists('sonic_api_rate')) {
    /** Rate-limit yang menghormati toggle Kustomisasi sonic_api_rate. */
    function sonic_api_rate($bucket, $limit = 60, $window = 60) {
        global $sysconf;
        if ((int)($sysconf['template']['sonic_api_rate'] ?? 1) !== 1) return true;
        return sonic_rate_limit($bucket, $limit, $window);
    }
}
if (!function_exists('sonic_etag_send')) {
    /** Kirim body + ETag; hormati If-None-Match → 304 (hemat bandwidth). */
    function sonic_etag_send($body, $contentType = 'application/json; charset=utf-8', $maxAge = 60) {
        $etag = '"' . md5($body) . '"';
        header('Content-Type: ' . $contentType);
        header('ETag: ' . $etag);
        header('Cache-Control: public, max-age=' . (int)$maxAge);
        $inm = trim((string)($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''));
        if ($inm !== '' && $inm === $etag) { http_response_code(304); exit; }
        echo $body;
        exit;
    }
}